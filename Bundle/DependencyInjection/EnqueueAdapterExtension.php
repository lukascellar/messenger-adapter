<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enqueue\MessengerAdapter\Bundle\DependencyInjection;

use Enqueue\MessengerAdapter\AmqpContextManager;
use Enqueue\MessengerAdapter\QueueInteropReceiver;
use Enqueue\MessengerAdapter\QueueInteropSender;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EnqueueAdapterExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);
        if (!$config['enabled']) {
            return;
        }

        $contextManager = new Definition(AmqpContextManager::class, array(
            new Reference('enqueue.transport.default.context'),
        ));

        $receiverDefinition = new Definition(QueueInteropReceiver::class, array(
            new Reference('messenger.transport.serializer'),
            $contextManager,
            $config['queue'],
            $config['topic'] ?: 'messages',
            $container->getParameter('kernel.debug'),
        ));
        $receiverDefinition->addTag('messenger.receiver');

        $senderDefinition = new Definition(QueueInteropSender::class, array(
            new Reference('messenger.transport.serializer'),
            $contextManager,
            $config['queue'],
            $config['topic'] ?: 'messages',
            $container->getParameter('kernel.debug'),
            $config['deliveryDelay'],
            $config['timeToLive'],
            $config['priority'],
        ));
        $senderDefinition->setPublic(true);
        $senderDefinition->addTag('messenger.sender');

        $container->setDefinitions(array(
            'enqueue_bridge.receiver' => $receiverDefinition,
            'enqueue_bridge.sender' => $senderDefinition,
        ));
    }
}
