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

	public static function callBack( \Parser $parser, $world = 'all', $vocation = 'all', $amount = '25' ) {
		if (empty($world)) {
			$world = 'all';
		}
		$worlds = ["all", "Ambra", "Antica", "Astera", "Axera", "Belobra", "Bombra", "Bona", "Calmera", "Castela", "Celebra", "Celesta", "Collabra", "Damora", "Descubra", "Dia", "Epoca", "Esmera", "Etebra", "Ferobra", "Firmera", "Flamera", "Gentebra", "Gladera", "Gravitera", "Guerribra", "Harmonia", "Havera", "Honbra", "Impulsa", "Inabra", "Issobra", "Jacabra", "Jadebra", "Jaguna", "Kalibra", "Kardera", "Kendria", "Lobera", "Luminera", "Lutabra", "Menera", "Monza", "Mykera", "Nadora", "Nefera", "Nevia", "Obscubra", "Ombra", "Ousabra", "Pacera", "Peloria", "Premia", "Pulsera", "Quelibra", "Quintera", "Rasteibra", "Refugia", "Retalia", "Runera", "Secura", "Serdebra", "Solidera", "Syrena", "Talera", "Thyria", "Tornabra", "Ustebra", "Utobra", "Venebra", "Vitera", "Vunira", "Wadira", "Wildera", "Wintera", "Yonabra", "Yovera", "Zuna", "Zunera"];
		if (!in_array($world, $worlds)) {
			return '<div class="error">' . wfMessage('tibiahighscores-error-world')->text() . '</div>';
		}
		if (empty($vocation)) {
			$vocation = 'all';
		}
		$vocations= ["all", "none", "druid", "knight", "paladin", "sorcerer"];
		if (!in_array($vocation, $vocations)) {
			return '<div class="error">' . wfMessage('tibiahighscores-error-vocation')->text() . '</div>';
		}
		$vocationAddon = '';
		if ($vocation != 'all') {
			$vocationAddon = '/' . $vocation;
		}
		if ( !is_numeric( $amount ) || intval( $amount ) <= 0 ) {
			return '<div class="error">' . wfMessage('tibiahighscores-error-amount')->text() . '</div>';
		}

		$cache = \ObjectCache::getInstance( CACHE_ANYTHING );

		$content = $cache->getWithSetCallback( 
			$cache->makeKey( 'tibiahighscores', $world, $vocation, $amount ),
			$cache::TTL_HOUR,
			function() use ( $world, $vocation, $vocationAddon, $amount ) {
				// Disable SSL validation while the servers don't get newer CA bundles
				$arrContextOptions=array(
					"ssl"=>array(
						"verify_peer"=>false,
						"verify_peer_name"=>false,
					),
				);
				$url = 'https://api.tibiadata.com/v4/highscores/' . $world . '/experience' . $vocationAddon;
				$json = file_get_contents($url, false, stream_context_create($arrContextOptions));
				$data = json_decode($json, true);
				$highscores = $data['highscores']['highscore_list'];
				$table = '<table class="wikitable"><tr><th></th><th>' . wfMessage('tibiahighscores-name')->text() . '</th><th>' . wfMessage('tibiahighscores-vocation')->text() . '</th><th>' . wfMessage('tibiahighscores-level')->text() . '</th><th>' . wfMessage('tibiahighscores-guild')->text() . '</th></tr>';
				for ($i = 0;$i < intval($amount);$i++) {
					if ( !empty($highscores[$i]['name']) ) {
						$urlCharacter = 'https://api.tibiadata.com/v4/character/' . str_replace(' ', '+', $highscores[$i]['name'])	;
						$json2 = file_get_contents($urlCharacter, false, stream_context_create($arrContextOptions));
						$data2 = json_decode($json2, true);
						$characters = $data2['character']['character'];
						$guildName = '';
						if ($characters && array_key_exists('guild', $characters) && $characters['guild']['name'] != null && $characters['guild']['name'] != "") {
							$guildName = '[https://www.tibia.com/community/?subtopic=guilds&page=view&GuildName=' . str_replace(' ', '+', $characters['guild']['name']) . ' ' . $characters['guild']['name'] . ']';
						}
						$table .= '<tr><td style="width: 50px text-align: center;">' . $highscores[$i]['rank'] . '</td><td>[https://www.tibia.com/community/?subtopic=characters&name=' . str_replace(' ', '+', $highscores[$i]['name']) . ' ' . $highscores[$i]['name'] . ']</td><td>' . $highscores[$i]['vocation'] . '</td><td style="text-align: center;">' . $highscores[$i]['level'] . '</td><td>' . $guildName . '</td></tr>';
					}
				}
				$table .= '</table>';
				return $table;
			}
		);
		return $content;
	}

}
