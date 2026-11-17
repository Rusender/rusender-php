<?php

declare(strict_types=1);

namespace RuSender\Tests;

use GuzzleHttp\Psr7\Response;
use RuSender\Exceptions\ApiException;
use RuSender\Exceptions\NotFoundException;
use RuSender\Tests\Support\TestCase;
use RuSender\Types\AcceptedEmailRecipient;
use RuSender\Types\Contact;

final class ResourcesTest extends TestCase
{
    private const ACCEPTED = [
        'id' => 'em_1',
        'status' => 'queued',
        'createdAt' => '2026-11-12T12:00:00Z',
        'recipients' => [['id' => 'r1', 'email' => 'a@example.ru', 'name' => null, 'type' => 'to', 'status' => 'queued', 'reason' => null]],
    ];

    public function testSendEmail(): void
    {
        $client = $this->client([self::ok(self::ACCEPTED, [], 202)]);
        $email = $client->emails->send([
            'from' => ['email' => 'no-reply@shop.ru', 'name' => 'Shop'],
            'to' => ['a@example.ru', ['email' => 'b@example.ru', 'name' => 'B']],
            'cc' => 'c@example.ru',
            'subject' => 'Order',
            'templateAlias' => 'order-created',
            'variables' => ['orderId' => 42],
        ]);
        self::assertSame('em_1', $email->id);
        self::assertInstanceOf(\DateTimeImmutable::class, $email->createdAt);
        self::assertInstanceOf(AcceptedEmailRecipient::class, $email->recipients[0]);
        self::assertSame('queued', $email->recipients[0]->status);

        $request = $this->request();
        self::assertSame(['POST', '/api/v1/public/emails'], [$request->getMethod(), $request->getUri()->getPath()]);
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame([
            'from' => ['email' => 'no-reply@shop.ru', 'name' => 'Shop'],
            'to' => [['email' => 'a@example.ru'], ['email' => 'b@example.ru', 'name' => 'B']],
            'cc' => [['email' => 'c@example.ru']],
            'subject' => 'Order',
            'templateAlias' => 'order-created',
            'variables' => ['orderId' => 42],
        ], $this->data());
    }

    public function testSendEmailAttachments(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'rusender');
        \assert(\is_string($path));
        file_put_contents($path, 'bye');
        $client = $this->client([self::ok(self::ACCEPTED, [], 202)]);
        $client->emails->send([
            'from' => 'no-reply@shop.ru',
            'to' => 'a@example.ru',
            'subject' => 'Invoice',
            'text' => 'See attached',
            'attachments' => [
                ['fileName' => 'a.txt', 'content' => 'hello'],
                ['fileName' => 'b.txt', 'path' => $path],
                ['fileId' => 101],
            ],
        ]);
        unlink($path);
        $data = $this->data();
        \assert(\is_array($data));
        self::assertSame([
            ['fileName' => 'a.txt', 'content' => 'aGVsbG8='],
            ['fileName' => 'b.txt', 'content' => 'Ynll'],
            ['fileId' => 101],
        ], $data['attachments']);
    }

    public function testPage(): void
    {
        $pagination = ['page' => 2, 'limit' => 20, 'totalItems' => 21, 'totalPages' => 2];
        $client = $this->client([self::ok(['items' => [['id' => 1, 'name' => 'Clients']]], ['pagination' => $pagination])]);
        $page = $client->lists->list(['q' => 'Cli', 'page' => 2]);
        self::assertCount(1, $page);
        self::assertSame('Clients', $page->items[0]->name);
        self::assertSame(2, $page->pagination->totalPages);
        foreach ($page as $item) {
            self::assertSame(1, $item->id);
        }
        self::assertSame('q=Cli&page=2', $this->request()->getUri()->getQuery());
    }

    public function testCursorPage(): void
    {
        $client = $this->client([self::ok(['items' => [['id' => 'em_1']], 'hasMore' => true, 'nextCursor' => 'c2'])]);
        $page = $client->emails->list(['limit' => 1]);
        self::assertTrue($page->hasMore);
        self::assertSame('c2', $page->nextCursor);
        self::assertSame('em_1', $page->items[0]->id);
    }

    public function testPlainListAndCampaignRecipients(): void
    {
        $client = $this->client([self::ok(['items' => [['id' => 1, 'key' => 'city']]]), self::ok(['items' => [['contactId' => 1]], 'hasMore' => false])]);
        self::assertSame('city', $client->lists->variables->list(3)[0]->key);
        $recipients = $client->campaigns->listRecipients(5);
        self::assertFalse($recipients['hasMore']);
        self::assertSame(1, $recipients['items'][0]->contactId);
    }

    public function testContactIdsBody(): void
    {
        $client = $this->client([self::ok(['listId' => 3, 'affectedContacts' => 2])]);
        self::assertSame(2, $client->lists->contacts->add(3, [10, 11])->affectedContacts);
        self::assertSame('/api/v1/public/lists/3/contacts/add', $this->request()->getUri()->getPath());
        self::assertSame(['contactIds' => [10, 11]], $this->data());
    }

    public function testCreateWithoutParametersSendsAnEmptyObject(): void
    {
        $client = $this->client([self::ok(['id' => 5])]);
        $client->campaigns->create();
        self::assertSame('{"data":{}}', (string) $this->request()->getBody());
    }

    public function testPostWithoutBody(): void
    {
        $client = $this->client([self::ok(['id' => 5])]);
        $client->campaigns->pause(5);
        $request = $this->request();
        self::assertSame('', (string) $request->getBody());
        self::assertFalse($request->hasHeader('Content-Type'));
        self::assertTrue($request->hasHeader('Idempotency-Key'));
    }

    public function testNullClearsAndMissingIsLeftOut(): void
    {
        $client = $this->client([self::ok(['id' => 1]), self::ok(['id' => 1]), self::ok(['id' => 1])]);
        $client->templates->update(1, ['name' => 'Order']);
        $client->templates->update(1, ['alias' => null]);
        $client->lists->variables->update(1, 2, ['defaultValue' => null]);
        self::assertSame(['name' => 'Order'], $this->data(0));
        self::assertSame(['alias' => null], $this->data(1));
        self::assertSame(['defaultValue' => null], $this->data(2));
    }

    public function testEmptyListIsSent(): void
    {
        $client = $this->client([self::ok(['id' => 1]), self::ok(['id' => 1])]);
        $client->segments->update(1, ['conditions' => []]);
        $client->campaigns->update(1, ['subject' => 'Hi', 'attachments' => []]);
        self::assertSame('{"data":{"conditions":[]}}', (string) $this->request(0)->getBody());
        self::assertSame(['subject' => 'Hi', 'attachments' => []], $this->data(1));
    }

    public function testScheduleSendsTheDateAsIso(): void
    {
        $client = $this->client([self::ok(['id' => 5])]);
        $client->campaigns->schedule(5, ['scheduledStartDate' => new \DateTimeImmutable('2026-12-01T12:00:00+03:00')]);
        self::assertSame(['scheduledStartDate' => '2026-12-01T09:00:00.000Z'], $this->data());
    }

    public function testTemplatesListUsesV2(): void
    {
        $client = $this->client([self::ok(['items' => []], self::PAGINATION)]);
        $client->templates->list(['name' => 'Order']);
        self::assertSame('/api/v2/public/templates', $this->request()->getUri()->getPath());
    }

    public function testUploadFilesAsMultipart(): void
    {
        $files = [
            ['id' => 1, 'fileName' => 'a.pdf', 'size' => 4, 'createdAt' => '2026-11-12T12:00:00Z'],
            ['id' => 2, 'fileName' => 'b.png', 'size' => 3, 'createdAt' => '2026-11-12T12:00:00Z'],
        ];
        $client = $this->client([self::ok(['files' => $files], [], 201)]);
        $result = $client->files->upload([['fileName' => 'a.pdf', 'content' => '%PDF'], ['fileName' => 'b.png', 'content' => "\x01\x02\x03"]]);
        self::assertSame([1, 2], array_map(static fn($file): mixed => $file->id, $result));
        $request = $this->request();
        self::assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderLine('Content-Type'));
        self::assertFalse($request->hasHeader('Idempotency-Key'));
        $body = (string) $request->getBody();
        self::assertStringContainsString('name="file"; filename="a.pdf"', $body);
        self::assertStringContainsString("\x01\x02\x03", $body);
    }

    public function testUploadIsNotRetried(): void
    {
        $client = $this->client([self::failure(503, 'UNAVAILABLE')]);
        try {
            $client->files->upload([['fileName' => 'a.pdf', 'content' => 'x']]);
            self::fail('no exception');
        } catch (ApiException) {
        }
        self::assertCount(1, $this->history);
    }

    public function testExportReturnsTheResponse(): void
    {
        $client = $this->client([new Response(200, ['Content-Type' => 'text/csv'], "email\na@example.ru\n")]);
        $response = $client->contacts->export(['format' => 'csv']);
        self::assertSame("email\na@example.ru\n", (string) $response->getBody());
    }

    public function testFailedDownloadThrows(): void
    {
        $client = $this->client([self::failure(404, 'FILE_NOT_FOUND', 'File not found')]);
        $this->expectException(NotFoundException::class);
        $client->files->download(1);
    }

    public function testNestedObjectsAndJson(): void
    {
        $client = $this->client([self::ok(['items' => [[
            'id' => 1,
            'email' => 'a@example.ru',
            'lists' => [['id' => 7, 'name' => 'Clients']],
            'createdAt' => '2026-11-12T12:00:00.000Z',
        ]]], self::PAGINATION)]);
        $contact = $client->contacts->list()->items[0];
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame('Clients', $contact->lists[0]->name ?? null);
        self::assertSame(
            '{"id":1,"email":"a@example.ru","lists":[{"id":7,"name":"Clients"}],"createdAt":"2026-11-12T12:00:00.000+00:00"}',
            json_encode($contact),
        );
    }
}
