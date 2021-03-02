<?php

declare(strict_types=1);

namespace spec\Akeneo\Connectivity\Connection\Infrastructure\Webhook\Client;

use Akeneo\Connectivity\Connection\Application\Webhook\Service\EventsApiDebugWebhookClientLogger;
use Akeneo\Connectivity\Connection\Application\Webhook\Service\Logger\SendApiEventRequestLogger;
use Akeneo\Connectivity\Connection\Domain\Webhook\Client\WebhookRequest;
use Akeneo\Connectivity\Connection\Infrastructure\Webhook\RequestHeaders;
use Akeneo\Platform\Component\EventQueue\Author;
use Akeneo\Connectivity\Connection\Domain\Webhook\Model\Read\ActiveWebhook;
use Akeneo\Connectivity\Connection\Domain\Webhook\Model\WebhookEvent;
use Akeneo\Connectivity\Connection\Infrastructure\Webhook\Client\GuzzleWebhookClient;
use Akeneo\Connectivity\Connection\Infrastructure\Webhook\Client\Signature;
use Akeneo\Platform\Component\EventQueue\Event;
use Akeneo\Platform\Component\EventQueue\EventInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PhpSpec\ObjectBehavior;
use PHPUnit\Framework\Assert;
use Prophecy\Argument;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

/**
 * @author    Thomas Galvaing <thomas.galvaing@akeneo.com>
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GuzzleWebhookClientSpec extends ObjectBehavior
{
    public function let(
        SendApiEventRequestLogger $sendApiEventRequestLogger,
        EventsApiDebugWebhookClientLogger $debugLogger
    ): void {
        $this->beConstructedWith(
            new Client(),
            new JsonEncoder(),
            $sendApiEventRequestLogger,
            $debugLogger,
            ['timeout' => 0.5, 'concurrency' => 1]
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(GuzzleWebhookClient::class);
    }

    public function it_sends_webhook_requests_in_bulk(
        SendApiEventRequestLogger $sendApiEventRequestLogger,
        EventsApiDebugWebhookClientLogger $debugLogger
    ): void {

        $mock = new MockHandler(
            [
                new Response(200, ['Content-Length' => 0]),
                new Response(200, ['Content-Length' => 0]),
            ]
        );

        $container = [];
        $history = Middleware::history($container);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $this->beConstructedWith(
            new Client(['handler' => $handlerStack]),
            new JsonEncoder(),
            $sendApiEventRequestLogger,
            $debugLogger,
            ['timeout' => 0.5, 'concurrency' => 1]
        );

        $author = Author::fromNameAndType('julia', Author::TYPE_UI);
        $request1 = new WebhookRequest(
            new ActiveWebhook('ecommerce', 0, 'a_secret', 'http://localhost/webhook1'),
            [
                new WebhookEvent(
                    'product.created',
                    '7abae2fe-759a-4fce-aa43-f413980671b3',
                    '2020-01-01T00:00:00+00:00',
                    $author,
                    'staging.akeneo.com',
                    ['data_1'],
                    $this->createEvent($author, ['data_1'], 1577836800, '7abae2fe-759a-4fce-aa43-f413980671b3')
                ),
            ]
        );

        $request2 = new WebhookRequest(
            new ActiveWebhook('erp', 1, 'a_secret', 'http://localhost/webhook2'),
            [
                new WebhookEvent(
                    'product.created',
                    '7abae2fe-759a-4fce-aa43-f413980671b3',
                    '2020-01-01T00:00:00+00:00',
                    $author,
                    'staging.akeneo.com',
                    ['data_2'],
                    $this->createEvent($author, ['data_2'], 1577836800, '7abae2fe-759a-4fce-aa43-f413980671b3')
                ),
            ]
        );

        $this->bulkSend([$request1, $request2]);


        Assert::assertCount(2, $container);

        // Request 1

        $request = $this->findRequest($container, 'http://localhost/webhook1');

        Assert::assertNotNull($request);

        $body = '{"events":[{"action":"product.created","event_id":"7abae2fe-759a-4fce-aa43-f413980671b3","event_datetime":"2020-01-01T00:00:00+00:00","author":"julia","author_type":"ui","pim_source":"staging.akeneo.com","data":["data_1"]}]}';
        Assert::assertEquals($body, (string)$request->getBody());

        $timestamp = (int)$request->getHeader(RequestHeaders::HEADER_REQUEST_TIMESTAMP)[0];
        $signature = Signature::createSignature('a_secret', $timestamp, $body);
        Assert::assertEquals($signature, $request->getHeader(RequestHeaders::HEADER_REQUEST_SIGNATURE)[0]);

        $debugLogger->logEventsApiRequestSucceed(
            'ecommerce',
            $request1->apiEvents(),
            'http://localhost/webhook1',
            200,
            Argument::any()
        )->shouldBeCalled();

        // Request 2

        $request = $this->findRequest($container, 'http://localhost/webhook2');
        Assert::assertNotNull($request);

        $body = '{"events":[{"action":"product.created","event_id":"7abae2fe-759a-4fce-aa43-f413980671b3","event_datetime":"2020-01-01T00:00:00+00:00","author":"julia","author_type":"ui","pim_source":"staging.akeneo.com","data":["data_2"]}]}';
        Assert::assertEquals($body, (string)$request->getBody());

        $timestamp = (int)$request->getHeader(RequestHeaders::HEADER_REQUEST_TIMESTAMP)[0];
        $signature = Signature::createSignature('a_secret', $timestamp, $body);
        Assert::assertEquals($signature, $request->getHeader(RequestHeaders::HEADER_REQUEST_SIGNATURE)[0]);

        $debugLogger->logEventsApiRequestSucceed(
            'erp',
            $request2->apiEvents(),
            'http://localhost/webhook2',
            200,
            Argument::any()
        )->shouldBeCalled();
    }

    private function findRequest(array $container, string $url): ?Request
    {
        foreach ($container as $transaction) {
            if ($url === (string)$transaction['request']->getUri()) {
                return $transaction['request'];
            }
        }

        return null;
    }

    private function createEvent(Author $author, array $data, int $timestamp, string $uuid): EventInterface
    {
        return new class($author, $data, $timestamp, $uuid) extends Event {
            public function getName(): string
            {
                return 'product.created';
            }
        };
    }
}
