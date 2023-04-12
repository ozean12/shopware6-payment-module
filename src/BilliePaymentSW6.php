<?php

declare(strict_types=1);

/*
 * Copyright (c) Billie GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Billie\BilliePayment;

use Billie\BilliePayment\Bootstrap\AbstractBootstrap;
use Billie\BilliePayment\Bootstrap\Database;
use Billie\BilliePayment\Bootstrap\PaymentMethods;
use Billie\BilliePayment\Bootstrap\PluginConfig;
use Billie\Sdk\HttpClient\BillieClient;
use Exception;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;

class BilliePaymentSW6 extends Plugin
{
    public function install(Plugin\Context\InstallContext $installContext): void
    {
        $bootstrapper = $this->getBootstrapClasses($installContext);
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->preInstall();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->install();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->postInstall();
        }
    }

    public function update(Plugin\Context\UpdateContext $updateContext): void
    {
        $bootstrapper = $this->getBootstrapClasses($updateContext);
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->preUpdate();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->update();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->postUpdate();
        }
    }

    public function uninstall(Plugin\Context\UninstallContext $uninstallContext): void
    {
        $bootstrapper = $this->getBootstrapClasses($uninstallContext);
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->preUninstall();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->uninstall($uninstallContext->keepUserData());
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->postUninstall();
        }
    }

    public function deactivate(Plugin\Context\DeactivateContext $deactivateContext): void
    {
        $bootstrapper = $this->getBootstrapClasses($deactivateContext);
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->preDeactivate();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->deactivate();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->postDeactivate();
        }
    }

    public function activate(Plugin\Context\ActivateContext $activateContext): void
    {
        $bootstrapper = $this->getBootstrapClasses($activateContext);
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->preActivate();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->activate();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->postActivate();
        }
    }

    /**
     * @return AbstractBootstrap[]
     */
    protected function getBootstrapClasses(Plugin\Context\InstallContext $context): array
    {
        /** @var AbstractBootstrap[] $bootstrapper */
        $bootstrapper = [
            new Database(),
            new PaymentMethods(),
            new PluginConfig(),
        ];

        /** @var EntityRepository $pluginRepository */
        $pluginRepository = $this->container->get('plugin.repository');
        $plugins = $pluginRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('baseClass', get_class($this))),
            $context->getContext()
        );
        $plugin = $plugins->first();
        // $logger = new FileLogger($this->container->getParameter('kernel.logs_dir'));
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->setInstallContext($context);
            // $bootstrap->setLogger($logger);
            $bootstrap->setContainer($this->container);
            $bootstrap->injectServices();
            $bootstrap->setPlugin($plugin);
        }

        return $bootstrapper;
    }
}

if (class_exists(BillieClient::class) === false) {
    $autoloaderPath = dirname(__DIR__) . '/vendor/autoload.php';
    if (file_exists($autoloaderPath)) {
        /** @noinspection PhpIncludeInspection */
        require_once $autoloaderPath;
    } else {
        throw new Exception('Missing Billie dependencies! Please run `composer require billie/shopware6-module` in project directory');
    }
}
