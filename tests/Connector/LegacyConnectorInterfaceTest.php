<?php

/**
 * @author    Joffrey Demetz <joffrey.demetz@gmail.com>
 * @license   MIT License; <https://opensource.org/licenses/MIT>
 */

namespace JDZ\Authentication\Tests\Connector;

use JDZ\Authentication\Connector\ArrayConnector;
use JDZ\Authentication\Connector\ConnectorInterface as LegacyConnectorInterface;
use JDZ\Authentication\Contract\ConnectorInterface;
use PHPUnit\Framework\TestCase;

class LegacyConnectorInterfaceTest extends TestCase
{
    public function testThePre360NameResolvesToTheSameInterface(): void
    {
        $this->assertTrue(\interface_exists(LegacyConnectorInterface::class));

        $legacy = new \ReflectionClass(LegacyConnectorInterface::class);

        $this->assertSame(ConnectorInterface::class, $legacy->getName());
    }

    public function testAShippedConnectorSatisfiesBothNames(): void
    {
        $reflection = new \ReflectionClass(ArrayConnector::class);

        $this->assertTrue($reflection->implementsInterface(ConnectorInterface::class));
        $this->assertTrue($reflection->implementsInterface(LegacyConnectorInterface::class));
    }
}
