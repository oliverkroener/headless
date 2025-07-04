<?php

/*
 * This file is part of the "headless" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

declare(strict_types=1);

namespace FriendsOfTYPO3\Headless\Tests\Unit\DataProcessing;

use FriendsOfTYPO3\Headless\DataProcessing\RootSitesProcessor;
use FriendsOfTYPO3\Headless\Tests\Unit\DataProcessing\RootSiteProcessing\TestDomainSchema;
use FriendsOfTYPO3\Headless\Tests\Unit\DataProcessing\RootSiteProcessing\TestSiteProvider;
use InvalidArgumentException;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use stdClass;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RootSitesProcessorTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        $this->resetSingletonInstances = true;

        parent::setUp();

        $c = new Container();
        $c->set(EventDispatcherInterface::class, $this->prophesize(EventDispatcherInterface::class)->reveal());
        GeneralUtility::setContainer($c);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(GeneralUtility::class, 'container'))->setValue(null, null);
        parent::tearDown();
    }

    public function testCustomImplementation(): void
    {
        $processor = new RootSitesProcessor();

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $contentObjectRenderer->start([], 'tt_content');
        $contentObjectRenderer->data['uid'] = 1;
        $conf = [];
        $conf['siteProvider'] = TestSiteProvider::class;
        $conf['siteSchema'] = TestDomainSchema::class;

        self::assertEquals([
            'sites' => [
                [
                    'name' => 'frontend.tld',
                    'baseURL' => 'https://frontend.tld',
                    'api' => ['baseURL' => '/proxy/'],
                    'i18n' => [
                        'locales' => ['en_US'],
                        'defaultLocale' => 'en_US',
                    ],
                ],
                [
                    'name' => 'frontend.tld',
                    'baseURL' => 'https://frontend.tld',
                    'api' => ['baseURL' => '/proxy/'],
                    'i18n' => [
                        'locales' => ['en_US'],
                        'defaultLocale' => 'en_US',
                    ],
                ],
            ],
        ], $processor->process($contentObjectRenderer, [], $conf, []));
    }

    public function testObjectNotSet()
    {
        $processor = new RootSitesProcessor();

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $contentObjectRenderer->start([], 'tt_content');

        $conf = [];
        self::assertEquals([], $processor->process($contentObjectRenderer, [], $conf, []));
    }

    public function testFeatureEnabledButWrongSiteProvider(): void
    {
        $processor = new RootSitesProcessor();

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $contentObjectRenderer->start([], 'tt_content');
        $contentObjectRenderer->data['uid'] = 1;
        $conf = [];
        $conf['siteProvider'] = stdClass::class;
        $this->expectException(InvalidArgumentException::class);
        self::assertEquals([], $processor->process($contentObjectRenderer, [], $conf, []));
    }

    public function testFeatureEnabledButWrongSiteSchema(): void
    {
        $processor = new RootSitesProcessor();

        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObjectRenderer->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $contentObjectRenderer->start([], 'tt_content');
        $contentObjectRenderer->data['uid'] = 1;
        $conf = [];
        $conf['siteSchema'] = stdClass::class;
        $this->expectException(InvalidArgumentException::class);
        self::assertEquals([], $processor->process($contentObjectRenderer, [], $conf, []));
    }
}
