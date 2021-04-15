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
use Billie\Sdk\HttpClient\BillieClient;
use Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;

class BilliePayment extends Plugin
{
    public function install(Plugin\Context\InstallContext $context): void
    {
        $bootstrapper = $this->getBootstrapClasses($context);
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

    /**
     * @return AbstractBootstrap[]
     */
    protected function getBootstrapClasses(Plugin\Context\InstallContext $context)
    {
        /** @var AbstractBootstrap[] $bootstrapper */
        $bootstrapper = [
            new Database(),
            new PaymentMethods(),
        ];

        /** @var EntityRepositoryInterface $pluginRepository */
        $pluginRepository = $this->container->get('plugin.repository');
        $plugins = $pluginRepository->search((new Criteria())->addFilter(new EqualsFilter('baseClass', get_class($this))), Context::createDefaultContext());
        $plugin = $plugins->first();
        //$logger = new FileLogger($this->container->getParameter('kernel.logs_dir'));
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->setInstallContext($context);
            //$bootstrap->setLogger($logger);
            $bootstrap->setContainer($this->container);
            $bootstrap->injectServices();
            $bootstrap->setPlugin($plugin);
        }

        return $bootstrapper;
    }

    public function update(Plugin\Context\UpdateContext $context): void
    {
        $bootstrapper = $this->getBootstrapClasses($context);
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

    public function uninstall(Plugin\Context\UninstallContext $context): void
    {
        $bootstrapper = $this->getBootstrapClasses($context);
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->preUninstall();
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->uninstall($context->keepUserData());
        }
        foreach ($bootstrapper as $bootstrap) {
            $bootstrap->postUninstall();
        }
    }

    public function deactivate(Plugin\Context\DeactivateContext $context): void
    {
        $bootstrapper = $this->getBootstrapClasses($context);
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

    public function activate(Plugin\Context\ActivateContext $context): void
    {
        $bootstrapper = $this->getBootstrapClasses($context);
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

    public function boot(): void
    {
        parent::boot();
        if (class_exists(BillieClient::class) === false) {
            $autoloaderPath = dirname(__DIR__) . '/vendor/autoload.php';
            if (file_exists($autoloaderPath)) {
                /** @noinspection PhpIncludeInspection */
                require_once $autoloaderPath;
            } else {
                throw new Exception('Missing Billie dependencies! Please run `composer require billie/shopware6-module` in project directory');
            }
        }
    }
}
