<?php

namespace Tests;

use Google\Cloud\PubSub\BatchPublisher;
use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Topic;
use Mockery;
use PHPUnit\Framework\TestCase;
use milind\PubSub\GoogleCloud\GoogleCloudPubSubAdapter;

class GoogleCloudPubSubAdapterTest extends TestCase
{
    public function testGetClient()
    {
        $client = Mockery::mock(PubSubClient::class);
        $adapter = new GoogleCloudPubSubAdapter($client);
        $this->assertSame($client, $adapter->getClient());
    }

    public function testGetSetClientIdentifier()
    {
        $client = Mockery::mock(PubSubClient::class);
        $adapter = new GoogleCloudPubSubAdapter($client);
        $this->assertNull($adapter->getClientIdentifier());

        $adapter->setClientIdentifier('my_identifier');
        $this->assertEquals('my_identifier', $adapter->getClientIdentifier());
    }

    public function testGetSetAutoCreateTopics()
    {
        $client = Mockery::mock(PubSubClient::class);
        $adapter = new GoogleCloudPubSubAdapter($client);
        $this->assertTrue($adapter->areTopicsAutoCreated());

        $adapter->setAutoCreateTopics(false);
        $this->assertFalse($adapter->areTopicsAutoCreated());
    }

    public function testGetSetAutoCreateSubscriptions()
    {
        $client = Mockery::mock(PubSubClient::class);
        $adapter = new GoogleCloudPubSubAdapter($client);
        $this->assertTrue($adapter->areSubscriptionsAutoCreated());

        $adapter->setAutoCreateSubscriptions(false);
        $this->assertFalse($adapter->areSubscriptionsAutoCreated());
    }

    public function testGetSetBackgroundBatching()
    {
        $client = Mockery::mock(PubSubClient::class);
        $adapter = new GoogleCloudPubSubAdapter($client);
        $this->assertFalse($adapter->isBackgroundBatchingEnabled());

        $adapter->setBackgroundBatching(true);
        $this->assertTrue($adapter->isBackgroundBatchingEnabled());

        $adapter = new GoogleCloudPubSubAdapter($client, null, true, true, true);
        $this->assertTrue($adapter->isBackgroundBatchingEnabled());
    }

    public function testGetSetReturnImmediately()
    {
        $client = Mockery::mock(PubSubClient::class);
        $adapter = new GoogleCloudPubSubAdapter($client);
        $this->assertFalse($adapter->getReturnImmediately());

        $adapter->setReturnImmediately(true);
        $this->assertTrue($adapter->getReturnImmediately());
    }

    public function testGetSetReturnImmediatelyPause()
    {
        $client = Mockery::mock(PubSubClient::class);
        $adapter = new GoogleCloudPubSubAdapter($client);
        $this->assertEquals(500000, $adapter->getReturnImmediatelyPause());

        $adapter->setReturnImmediatelyPause(1000000);
        $this->assertEquals(1000000, $adapter->getReturnImmediatelyPause());
    }

    public function testPublishWhenTopicMustBeCreated()
    {
        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(false);
        $topic->shouldReceive('create')
            ->once();
        $topic->shouldReceive('publish')
            ->with([
                'data' => '{"hello":"world"}',
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);

        $adapter->publish('channel_name', ['hello' => 'world']);
    }

    public function testPublishWhenTopicMustBeCreatedAndBackgroundBatchingIsEnabled()
    {
        $batchPublisher = Mockery::mock(BatchPublisher::class);

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(false);
        $topic->shouldReceive('create')
            ->once();

        $topic->shouldReceive('batchPublisher')
            ->once()
            ->andReturn($batchPublisher);

        $batchPublisher->shouldReceive('publish')
            ->with([
                'data' => '{"hello":"world"}',
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);
        $adapter->setBackgroundBatching(true);

        $adapter->publish('channel_name', ['hello' => 'world']);
    }

    public function testPublishWhenTopicExists()
    {
        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('publish')
            ->with([
                'data' => '{"hello":"world"}',
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);

        $adapter->publish('channel_name', ['hello' => 'world']);
    }

    public function testPublishWhenTopicExistsAndBackgroundBatchingIsEnabled()
    {
        $batchPublisher = Mockery::mock(BatchPublisher::class);

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('batchPublisher')
            ->once()
            ->andReturn($batchPublisher);

        $batchPublisher->shouldReceive('publish')
            ->with([
                'data' => '{"hello":"world"}',
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);
        $adapter->setBackgroundBatching(true);

        $adapter->publish('channel_name', ['hello' => 'world']);
    }

    public function testPublishWhenAutoTopicCreationIsDisabled()
    {
        $topic = Mockery::mock(Topic::class);
        $topic->shouldNotHaveReceived('exists');
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('publish')
            ->with([
                'data' => '{"hello":"world"}',
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client, null, false);

        $adapter->publish('channel_name', ['hello' => 'world']);
    }

    public function testPublishWhenAutoTopicCreationIsDisabledAndBackgroundBatchingIsEnabled()
    {
        $batchPublisher = Mockery::mock(BatchPublisher::class);

        $topic = Mockery::mock(Topic::class);
        $topic->shouldNotHaveReceived('exists');
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('batchPublisher')
            ->once()
            ->andReturn($batchPublisher);

        $batchPublisher->shouldReceive('publish')
            ->with([
                'data' => '{"hello":"world"}',
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client, null, false);
        $adapter->setBackgroundBatching(true);

        $adapter->publish('channel_name', ['hello' => 'world']);
    }

    public function testPublishBatch()
    {
        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldReceive('publishBatch')
            ->with([
                ['data' => '{"hello":"world"}'],
                ['data' => '"booo!"'],
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);

        $messages = [
            ['hello' => 'world'],
            'booo!',
        ];
        $adapter->publishBatch('channel_name', $messages);
    }

    public function testPublishBatchWhenBackgroundBatchingIsEnabled()
    {
        $batchPublisher = Mockery::mock(BatchPublisher::class);

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldReceive('batchPublisher')
            ->once()
            ->andReturn($batchPublisher);

        $batchPublisher->shouldReceive('publish')
            ->with([
                'data' => '{"hello":"world"}',
            ])
            ->once();
        $batchPublisher->shouldReceive('publish')
            ->with([
                'data' => '"booo!"',
            ])
            ->once();

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);
        $adapter->setBackgroundBatching(true);

        $messages = [
            ['hello' => 'world'],
            'booo!',
        ];
        $adapter->publishBatch('channel_name', $messages);
    }

    public function testSubscribeWhenSubscriptionMustBeCreated()
    {
        $message1 = new Message(['data' => '{"hello":"world"}'], ['ackId' => 1]);
        $message2 = new Message(['data' => '"this is a string"'], ['ackId' => 2]);
        $message3 = new Message(['data' => '"unsubscribe"'], ['ackId' => 3]);

        $messageBatch1 = [
            $message1,
            $message2,
        ];
        $messageBatch2 = [
            $message3,
        ];

        $subscription = Mockery::mock(Subscription::class);
        $subscription->shouldReceive('exists')
            ->once()
            ->andReturn(false);
        $subscription->shouldReceive('create')
            ->once();
        $subscription->shouldReceive('pull')
            ->with([
                'grpcOptions' => [
                    'timeoutMillis' => null,
                ],
                'maxMessages' => 1000,
                'returnImmediately' => false
            ])
            ->once()
            ->andReturn($messageBatch1);

        $subscription->shouldReceive('acknowledge')
            ->with($message1)
            ->once();
        $subscription->shouldReceive('acknowledge')
            ->with($message2)
            ->once();
        $subscription->shouldReceive('pull')
            ->with([
                'grpcOptions' => [
                    'timeoutMillis' => null,
                ],
                'maxMessages' => 1000,
                'returnImmediately' => false
            ])
            ->once()
            ->andReturn($messageBatch2);
        $subscription->shouldReceive('acknowledge')
            ->with($message3)
            ->once();

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('subscription')
            ->with('default.channel_name')
            ->once()
            ->andReturn($subscription);

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);

        $handler1 = Mockery::mock(\stdClass::class);
        $handler1->shouldReceive('handle')
            ->with(['hello' => 'world'])
            ->once();
        $handler1->shouldReceive('handle')
            ->with('this is a string')
            ->once();

        $adapter->subscribe('channel_name', [$handler1, 'handle']);
    }

    public function testSubscribeWhenSubscriptionExists()
    {
        $message1 = new Message(['data' => '{"hello":"world"}'], ['ackId' => 1]);
        $message2 = new Message(['data' => '"this is a string"'], ['ackId' => 2]);
        $message3 = new Message(['data' => '"unsubscribe"'], ['ackId' => 3]);

        $messageBatch1 = [
            $message1,
            $message2,
        ];
        $messageBatch2 = [
            $message3,
        ];

        $subscription = Mockery::mock(Subscription::class);
        $subscription->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $subscription->shouldNotHaveReceived('create');
        $subscription->shouldReceive('pull')
            ->with([
                'grpcOptions' => [
                    'timeoutMillis' => null,
                ],
                'maxMessages' => 1000,
                'returnImmediately' => false
            ])
            ->once()
            ->andReturn($messageBatch1);
        $subscription->shouldReceive('acknowledge')
            ->with($message1)
            ->once();
        $subscription->shouldReceive('acknowledge')
            ->with($message2)
            ->once();
        $subscription->shouldReceive('pull')
            ->with([
                'grpcOptions' => [
                    'timeoutMillis' => null,
                ],
                'maxMessages' => 1000,
                'returnImmediately' => false
            ])
            ->once()
            ->andReturn($messageBatch2);
        $subscription->shouldReceive('acknowledge')
            ->with($message3)
            ->once();

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('subscription')
            ->with('default.channel_name')
            ->once()
            ->andReturn($subscription);

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client);

        $handler1 = Mockery::mock(\stdClass::class);
        $handler1->shouldReceive('handle')
            ->with(['hello' => 'world'])
            ->once();
        $handler1->shouldReceive('handle')
            ->with('this is a string')
            ->once();

        $adapter->subscribe('channel_name', [$handler1, 'handle']);
    }

    public function testSubscribeWhenAutoTopicCreationIsDisabled()
    {
        $message1 = new Message(['data' => '{"hello":"world"}'], ['ackId' => 1]);
        $message2 = new Message(['data' => '"this is a string"'], ['ackId' => 2]);
        $message3 = new Message(['data' => '"unsubscribe"'], ['ackId' => 3]);

        $messageBatch1 = [
            $message1,
            $message2,
        ];
        $messageBatch2 = [
            $message3,
        ];

        $subscription = Mockery::mock(Subscription::class);
        $subscription->shouldNotHaveReceived('exists');
        $subscription->shouldNotHaveReceived('create');
        $subscription->shouldReceive('pull')
            ->with([
                'grpcOptions' => [
                    'timeoutMillis' => null,
                ],
                'maxMessages' => 1000,
                'returnImmediately' => false
            ])
            ->once()
            ->andReturn($messageBatch1);
        $subscription->shouldReceive('acknowledge')
            ->with($message1)
            ->once();
        $subscription->shouldReceive('acknowledge')
            ->with($message2)
            ->once();
        $subscription->shouldReceive('pull')
            ->with([
                'grpcOptions' => [
                    'timeoutMillis' => null,
                ],
                'maxMessages' => 1000,
                'returnImmediately' => false
            ])
            ->once()
            ->andReturn($messageBatch2);
        $subscription->shouldReceive('acknowledge')
            ->with($message3)
            ->once();

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('subscription')
            ->with('default.channel_name')
            ->once()
            ->andReturn($subscription);

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $adapter = new GoogleCloudPubSubAdapter($client, null, true, false);

        $handler1 = Mockery::mock(\stdClass::class);
        $handler1->shouldReceive('handle')
            ->with(['hello' => 'world'])
            ->once();
        $handler1->shouldReceive('handle')
            ->with('this is a string')
            ->once();

        $adapter->subscribe('channel_name', [$handler1, 'handle']);
    }

    public function testSubscribeWhenReturnImmediatelyIsEnabled()
    {
        $message1 = new Message(['data' => '{"hello":"world"}'], ['ackId' => 1]);
        $message2 = new Message(['data' => '"this is a string"'], ['ackId' => 2]);
        $message3 = new Message(['data' => '"unsubscribe"'], ['ackId' => 3]);

        $messageBatch1 = [
            $message1,
            $message2,
        ];

        $messageBatch2 = [
            $message3,
        ];

        $subscription = Mockery::mock(Subscription::class);
        $subscription->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $subscription->shouldNotHaveReceived('create');

        $expectedPullOptions = [
            'grpcOptions' => [
                'timeoutMillis' => null,
            ],
            'maxMessages' => 1000,
            'returnImmediately' => true
        ];

        $subscription->shouldReceive('pull')
            ->with($expectedPullOptions)
            ->once()
            ->andReturn($messageBatch1);
        $subscription->shouldReceive('acknowledge')
            ->with($message1)
            ->once();
        $subscription->shouldReceive('acknowledge')
            ->with($message2)
            ->once();

        $subscription->shouldReceive('pull')
            ->with($expectedPullOptions)
            ->once()
            ->andReturn($messageBatch2);
        $subscription->shouldReceive('acknowledge')
            ->with($message3)
            ->once();

        $topic = Mockery::mock(Topic::class);
        $topic->shouldReceive('exists')
            ->once()
            ->andReturn(true);
        $topic->shouldNotHaveReceived('create');
        $topic->shouldReceive('subscription')
            ->with('default.channel_name')
            ->once()
            ->andReturn($subscription);

        $client = Mockery::mock(PubSubClient::class);
        $client->shouldReceive('topic')
            ->with('channel_name')
            ->once()
            ->andReturn($topic);

        $handler1 = Mockery::mock(\stdClass::class);
        $handler1->shouldReceive('handle')
            ->with(['hello' => 'world'])
            ->once();
        $handler1->shouldReceive('handle')
            ->with('this is a string')
            ->once();

        $adapter = new GoogleCloudPubSubAdapter($client);
        $adapter->setReturnImmediately(true);
        $adapter->subscribe('channel_name', [$handler1, 'handle']);
    }
}
