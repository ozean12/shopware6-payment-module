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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class BilliePaymentSW6 extends Plugin
{
    public function install(InstallContext $installContext): void
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

    public function update(UpdateContext $updateContext): void
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

    public function uninstall(UninstallContext $uninstallContext): void
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

    public function deactivate(DeactivateContext $deactivateContext): void
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

    public function activate(ActivateContext $activateContext): void
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

    public function executeComposerCommands(): bool
    {
        return true;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $locator = new FileLocator('Resources/config');
        $resolver = new LoaderResolver([
            new YamlFileLoader($container, $locator),
            new GlobFileLoader($container, $locator),
            new DirectoryLoader($container, $locator),
        ]);
        (new DelegatingLoader($resolver))
            ->load(\rtrim($this->getPath(), '/') . '/Resources/config/{packages}/*.yaml', 'glob');
    }

    /**
     * @return AbstractBootstrap[]
     */
    protected function getBootstrapClasses(InstallContext $context): array
    {
        /** @var AbstractBootstrap[] $bootstrapper */
        $bootstrapper = [
            new Database(),
            new PaymentMethods(),
            new PluginConfig(),
        ];

        /** @var EntityRepository<Plugin\PluginCollection> $pluginRepository */
        $pluginRepository = $this->container->get('plugin.repository');

        /** @var Plugin\PluginEntity $plugin */
        $plugin = $pluginRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('baseClass', static::class))->setLimit(1),
            $context->getContext()
        )->first();

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
