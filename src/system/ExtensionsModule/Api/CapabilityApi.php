<?php

declare(strict_types=1);

/*
 * This file is part of the Zikula package.
 *
 * Copyright Zikula Foundation - https://ziku.la/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\ExtensionsModule\Api;

use Zikula\ExtensionsModule\Api\ApiInterface\CapabilityApiInterface;
use Zikula\ExtensionsModule\Constant;
use Zikula\ExtensionsModule\Entity\ExtensionEntity;
use Zikula\ExtensionsModule\Entity\Repository\ExtensionRepository;
use Zikula\ExtensionsModule\Entity\RepositoryInterface\ExtensionRepositoryInterface;

class CapabilityApi implements CapabilityApiInterface
{
    /**
     * @var ExtensionRepository
     */
    private $extensionRepository;

    /**
     * @var ExtensionEntity[]
     */
    private $extensionsByCapability = [];

    /**
     * @var ExtensionEntity[]
     */
    private $extensionsByName = [];

    public function __construct(ExtensionRepositoryInterface $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * Load extensions into private property cache.
     */
    private function load(): void
    {
        $extensions = $this->extensionRepository->findBy(['state' => Constant::STATE_ACTIVE]);
        /** @var ExtensionEntity $extension */
        foreach ($extensions as $extension) {
            foreach ($extension->getCapabilities() as $capability => $definition) {
                $this->extensionsByCapability[$capability][] = $extension;
            }
            $this->extensionsByName[$extension->getName()] = $extension;
        }
    }

    public function getExtensionsCapableOf(string $capability): iterable
    {
        if (!isset($this->extensionsByCapability[$capability]) || empty($this->extensionsByCapability[$capability])) {
            $this->load();
        }

        return !empty($this->extensionsByCapability[$capability]) ? $this->extensionsByCapability[$capability] : [];
    }

    public function isCapable(string $extensionName, string $requestedCapability)
    {
        if (empty($this->extensionsByName[$extensionName])) {
            $this->extensionsByName[$extensionName] = $this->extensionRepository->findOneBy(['name' => $extensionName]);
        }
        $capabilities = $this->extensionsByName[$extensionName]->getCapabilities();

        return array_key_exists($requestedCapability, $capabilities)
            ? $capabilities[$requestedCapability]
            : false;
    }

    public function getCapabilitiesOf(string $extensionName): array
    {
        if (empty($this->extensionsByName[$extensionName])) {
            $this->extensionsByName[$extensionName] = $this->extensionRepository->findOneBy(['name' => $extensionName]);
        }

        return $this->extensionsByName[$extensionName]->getCapabilities();
    }
}
