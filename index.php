<?php

/*
 * ______         _ __  ___ ____
 * | ___ \       | |  \/  /  __ \
 * | |_/ /___  __| | .  . | /  \/
 * |    // _ \/ _` | |\/| | |
 * | |\ \  __/ (_| | |  | | \__/\
 * \_| \_\___|\__,_\_|  |_/\____/
 *
 * Copyright (C) RedMC Network, Inc - All Rights Reserved
 * You may use, distribute and modify this code under the
 * terms of the MIT license, which unfortunately won't be
 * written for another century.
 *
 * Written by xerenahmed <eren@redmc.me>, 2023
 *
 * @author RedMC Team
 * @link https://www.redmc.me/
 */

declare(strict_types=1);

define('pocketmine\BEDROCK_DATA_PATH', './vendor/pocketmine/bedrock-data/');

require_once 'vendor/autoload.php';

use pocketmine\data\bedrock\LegacyItemIdToStringIdMap;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\convert\GlobalItemTypeDictionary;
use pocketmine\network\mcpe\convert\ItemTranslator;

$tr_names = json_decode(file_get_contents('./resources/item-names-TR.json'), true);
$en_names = json_decode(file_get_contents('./resources/item-names-EN.json'), true);

$allItems = ItemFactory::getInstance()->getAllRegistered();
$output = [];
foreach ($allItems as $item) {
	$output[] = itemToData($item);
}

usort($output, function ($a, $b) {
	return $a['network']['id'] <=> $b['network']['id'];
});

file_put_contents('./generated.json', json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

function itemToData(Item $item): array {
	$legacyId = $item->getId();
	$legacyMeta = $item->getMeta();
	$map = LegacyItemIdToStringIdMap::getInstance();
	$baseIdentifier = $map->legacyToString($legacyId, $legacyMeta);
	$dictionary = GlobalItemTypeDictionary::getInstance()->getDictionary();
	[$netId, $netMeta] = ItemTranslator::getInstance()->toNetworkId($legacyId, $legacyMeta);
	$dictionaryIdentifier = $dictionary->fromIntId($netId);

	return [
		'identifier' => [
			'base' => $baseIdentifier,
			'dictionary' => $dictionaryIdentifier
		],
		'legacy' => [
			'id' => $legacyId,
			'meta' => $legacyMeta,
		],
		'network' => [
			'id' => $netId,
			'meta' => $netMeta,
		],
		'name' => [
			'pocketmine' => $item->getName(),
			'en' => nameForLang('en', [$dictionaryIdentifier, $baseIdentifier], $netMeta),
			'tr' => nameForLang('tr', [$dictionaryIdentifier, $baseIdentifier], $netMeta),
		]
	];
}

function nameForLang(string $lang, array $identifiers, int $meta): string {
	global $tr_names, $en_names;
	$table = match ($lang) {
		'tr' => $tr_names,
		'en' => $en_names,
		default =>
			throw new \InvalidArgumentException('Invalid language')
	};

	foreach ($identifiers as $identifier) {
		$entry = array_filter($table, fn($entry) => $entry['id'] === $identifier && (
			($entry['meta'] ?? 0) === $meta
		));
		if (count($entry) === 0) {
			continue;
		}

		return array_values($entry)[0]['name'];
	}

	return 'Unknown';
}
