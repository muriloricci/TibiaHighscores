<?php

namespace MediaWiki\Extension\TibiaHighscores;

/**
 * Hooks for TibiaHighscores extension
 *
 * @file
 * @ingroup Extensions
 */

class Hooks {

	public static function onParserFirstCallInit( \Parser $parser ) {
		$parser->setFunctionHook(
				'tibiahighscores',
				[Hooks::class, 'callBack']
			);
	}
	
	public static function callBack( \Parser $parser, $world = 'All', $vocation = '', $amount = '25' ) {
		if (empty($world)) {
			$world = 'All';			
		}
		$worlds = ["All", "Antica", "Assombra", "Astera", "Belluma", "Belobra", "Bona", "Calmera", "Carnera", "Celebra", "Celesta", "Concorda", "Cosera", "Damora", "Descubra", "Dibra", "Duna", "Emera", "Epoca", "Estela", "Faluna", "Ferobra", "Firmera", "Funera", "Furia", "Garnera", "Gentebra", "Gladera", "Harmonia", "Helera", "Honbra", "Impera", "Inabra", "Javibra", "Jonera", "Kalibra", "Kenora", "Lobera", "Luminera", "Lutabra", "Macabra", "Menera", "Mitigera", "Monza", "Nefera", "Noctera", "Nossobra", "Olera", "Ombra", "Pacera", "Pacembra", "Peloria", "Premia", "Pyra", "Quelibra", "Quintera", "Refugia", "Relania", "Relembra", "Secura", "Serdebra", "Serenebra", "Solidera", "Talera", "Torpera", "Tortura", "Unica", "Venebra", "Vita", "Vunira", "Wintera", "Xandebra", "Xylona", "Yonabra", "Ysolera", "Zenobra", "Zuna", "Zunera"];
		if (!in_array($world, $worlds)) {
			return '<div class="error">' . wfMessage('tibiahighscores-error-world')->text() . '</div>';
		}
		if (empty($vocation)) {
			$vocation = 'any';
		}
		$vocations= ["any", "none", "druid", "knight", "paladin", "sorcerer"];
		if (!in_array($vocation, $vocations)) {
			return '<div class="error">' . wfMessage('tibiahighscores-error-vocation')->text() . '</div>';
		}
		$vocationAddon = '';
		if ($vocation != 'any') {
			$vocationAddon = '/' . $vocation;
		}
		if ( !is_numeric( $amount ) || intval( $amount ) <= 0 ) {
			return '<div class="error">' . wfMessage('tibiahighscores-error-amount')->text() . '</div>';
		}
		
		$cache = \ObjectCache::getInstance( CACHE_ANYTHING );

		$content = $cache->getWithSetCallback( $cache->makeKey( 'tibiahighscores', $world, $vocation, $amount ), $cache::TTL_HOUR, function() use ( $world, $vocation, $vocationAddon, $amount ) {
			$url = 'https://api.tibiadata.com/v2/highscores/' . $world . '/experience' . $vocationAddon . '.json';
			$json = file_get_contents($url);
			$data = json_decode($json, true);
			$highscores = $data['highscores']['data'];
			$table = '<table class="wikitable"><tr><th></th><th>' . wfMessage('tibiahighscores-name')->text() . '</th><th>' . wfMessage('tibiahighscores-vocation')->text() . '</th><th>' . wfMessage('tibiahighscores-level')->text() . '</th><th>' . wfMessage('tibiahighscores-guild')->text() . '</th></tr>';
			for ($i = 0;$i < intval($amount);$i++) {
				$urlCharacter = 'https://api.tibiadata.com/v2/characters/' . str_replace(' ', '+', $highscores[$i]['name']) . '.json';
				$json2 = file_get_contents($urlCharacter);
				$data2 = json_decode($json2, true);
				$characters = $data2['characters']['data'];
				$guildName = '';
				if (array_key_exists('guild', $characters)) {
					$guildName = '[https://www.tibia.com/community/?subtopic=guilds&page=view&GuildName=' . str_replace(' ', '+', $characters['guild']['name']) . ' ' . $characters['guild']['name'] . ']';
				}
				$table .= '<tr><td>' . $highscores[$i]['rank'] . '</td><td>[https://www.tibia.com/community/?subtopic=characters&name=' . str_replace(' ', '+', $highscores[$i]['name']) . ' ' . $highscores[$i]['name'] . ']</td><td>' . $highscores[$i]['vocation'] . '</td><td>' . $highscores[$i]['level'] . '</td><td>' . $guildName . '</td></tr>';
			}
			$table .= '</table>';
			return $table;
		}, [ 'pcTTL' => $cache::TTL_HOUR ] );
		return $content;
	}

}
