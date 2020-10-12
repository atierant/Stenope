<?php

/*
 * This file is part of the "Tom32i/Content" bundle.
 *
 * @author Thomas Jarrand <thomas.jarrand@gmail.com>
 */

namespace Content\Provider\Factory;

use Content\Provider\ContentProviderInterface;

/**
 * Choose the first matching factory for type.
 */
class ContentProviderFactory implements ContentProviderFactoryInterface
{
    /** @var iterable<ContentProviderFactoryInterface>|ContentProviderFactoryInterface[] */
    private iterable $factories;

    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    public function create(string $type, array $config): ContentProviderInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->supports($type, $config)) {
                return $factory->create($type, $config);
            }
        }

        throw new \LogicException(sprintf('No content provider factory found for type "%s"', $type));
    }

    public function supports(string $type, array $config): bool
    {
        return true;
    }
}