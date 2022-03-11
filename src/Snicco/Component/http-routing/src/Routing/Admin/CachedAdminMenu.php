<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Admin;

use RuntimeException;

use function unserialize;

final class CachedAdminMenu implements AdminMenu
{
    /**
     * @var string[]
     */
    private array $serialized_menu_items = [];

    /**
     * @var list<AdminMenuItem>
     */
    private array $hydrated_items = [];

    /**
     * @param string[] $serialized_menu_items
     */
    public function __construct(array $serialized_menu_items)
    {
        $this->serialized_menu_items = $serialized_menu_items;
        $this->hydrated_items = [];
    }

    public function items(): array
    {
        return $this->getItems();
    }

    /**
     * @return list<AdminMenuItem>
     */
    private function getItems(): array
    {
        if ([] !== $this->hydrated_items) {
            return $this->hydrated_items;
        }

        $items = [];

        foreach ($this->serialized_menu_items as $item) {
            $res = unserialize($item);
            if (! $res instanceof AdminMenuItem) {
                throw new RuntimeException(
                    "Cached admin menu is corrupted.\nOne item could not be unserialized: [{$item}]"
                );
            }

            $items[] = $res;
        }

        $this->hydrated_items = $items;

        return $this->hydrated_items;
    }
}
