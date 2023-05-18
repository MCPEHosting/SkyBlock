<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace room17\SkyBlock\utils;


use JetBrains\PhpStorm\Pure;
use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class Utils {

    public static function parseItems(array $items): array {
        return array_filter(array_map("self::parseItem", $items), function($value) {
            return $value != null;
        });
    }

    public static function parseItem(string $item): ?Item {
        $parts = array_map("intval", explode(",", str_replace(" ", "", $item)));
        $parts0 =  $parts[0];
        $parts1 = $parts[1] ?? 0;
        $parts2 = $parts[2] ?? 1;
        return (count($parts) > 0) ? self::decodeItem("{$parts0}:{$parts1}:{$parts2}") /*ItemFactory::getInstance()->get($parts[0], $parts[1] ?? 0, $parts[2] ?? 1)*/ : null;
    }

    public static function translateColors(string $message): string {
        $message = str_replace("&", TextFormat::ESCAPE, $message);
        $message = str_replace("{BLACK}", TextFormat::BLACK, $message);
        $message = str_replace("{DARK_BLUE}", TextFormat::DARK_BLUE, $message);
        $message = str_replace("{DARK_GREEN}", TextFormat::DARK_GREEN, $message);
        $message = str_replace("{DARK_AQUA}", TextFormat::DARK_AQUA, $message);
        $message = str_replace("{DARK_RED}", TextFormat::DARK_RED, $message);
        $message = str_replace("{DARK_PURPLE}", TextFormat::DARK_PURPLE, $message);
        $message = str_replace("{ORANGE}", TextFormat::GOLD, $message);
        $message = str_replace("{GRAY}", TextFormat::GRAY, $message);
        $message = str_replace("{DARK_GRAY}", TextFormat::DARK_GRAY, $message);
        $message = str_replace("{BLUE}", TextFormat::BLUE, $message);
        $message = str_replace("{GREEN}", TextFormat::GREEN, $message);
        $message = str_replace("{AQUA}", TextFormat::AQUA, $message);
        $message = str_replace("{RED}", TextFormat::RED, $message);
        $message = str_replace("{LIGHT_PURPLE}", TextFormat::LIGHT_PURPLE, $message);
        $message = str_replace("{YELLOW}", TextFormat::YELLOW, $message);
        $message = str_replace("{WHITE}", TextFormat::WHITE, $message);
        $message = str_replace("{OBFUSCATED}", TextFormat::OBFUSCATED, $message);
        $message = str_replace("{BOLD}", TextFormat::BOLD, $message);
        $message = str_replace("{STRIKETHROUGH}", TextFormat::STRIKETHROUGH, $message);
        $message = str_replace("{UNDERLINE}", TextFormat::UNDERLINE, $message);
        $message = str_replace("{ITALIC}", TextFormat::ITALIC, $message);
        $message = str_replace("{RESET}", TextFormat::RESET, $message);
        return $message;
    }

    #[Pure]
    public static function encodeBlock(Block $block): string{
        return $block->getName();
    }

    public static function decodeBlock(string $object): Block{
        $ex = explode(";", $object);
        if (!isset($ex[1]) && !is_int($ex[0])) { // PM5-format
            $item = StringToItemParser::getInstance()->parse($object);
            $block = null;
            if ($item !== null) $block = $item->getBlock();
            else throw new \RuntimeException("Block $object not found in StringToItemParser, may its not registered there?");
            return $block;
        } else { // PM4-format
            if (!is_int($ex[1])) $ex[1] = 0;
            return RuntimeBlockStateRegistry::getInstance()->fromStateId(GlobalBlockStateHandlers::getDeserializer()->deserialize(GlobalBlockStateHandlers::getUpgrader()->upgradeIntIdMeta((int)$ex[0] ?? 1, (int)$ex[1] ?? 0)));
        }
    }

    public static function encodeItem(Item $item): string{
        return implode(";", [$item->getName(), $item->getCount()]);
    }

    public static function decodeItem(string $object): Item{
        $ex = explode(";", $object);
        if (isset($ex[0]) && !is_int($ex[0])) { // PM5-format
            if (!isset($ex[1]) || !is_int($ex[1])) $ex[1] = 1;
            $item = StringToItemParser::getInstance()->parse($object);
            if ($item === null) throw new \RuntimeException("Item $object not found in StringToItemParser, may its not registered there?");
            $item->setCount($ex[1]);
            return $item;
        } else { // PM4-format
            $id = $ex[0] ?? 0;
            $meta = isset($ex[2]) ? $ex[1] ?? 0 : 0;
            $count = isset($ex[2]) ? $ex[2] ?? $ex[1] ?? 1 : 1;
            $nbt = (new BigEndianNbtSerializer())->read($ex[3])->mustGetCompoundTag() ?? CompoundTag::create();
            return GlobalItemDataHandlers::getDeserializer()->deserializeStack(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt($id, $meta, $count, $nbt));
        }
    }
}