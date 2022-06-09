<?php

$accountsString = getenv('OWL_ACCOUNTS');
if($accountsString === false)
{
	echo 'No OWL_ACCOUNTS environment variable found!' . PHP_EOL;
	exit(1);
}

use GuzzleHttp\Exception\GuzzleException;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Phpfastcache\Helper\Psr16Adapter;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/config.php';
require __DIR__ . '/utils.php';


//if(!DEBUG && !shouldHeartbeat())
//{
//	die('Broadcast not live!' . PHP_EOL);
//}

try
{
	$cacheInstance = new Psr16Adapter('files', new ConfigurationOption([
			'path' => ROOT,
	]));
}
catch(PhpfastcacheDriverCheckException|PhpfastcacheLogicException|PhpfastcacheDriverException $e)
{
	die("Error: {$e->getMessage()}" . PHP_EOL);
}
catch(PhpfastcacheInvalidConfigurationException|PhpfastcacheInvalidTypeException $e)
{
	die("Error setting up config: {$e->getMessage()}" . PHP_EOL);
}

echo '—————————— ' . getTimestamp() . ' ——————————' . PHP_EOL;
$accounts = explode(',', $accountsString);
foreach($accounts as $accountId)
{
	echo "————— $accountId —————" . PHP_EOL;
	foreach(SERVICES as $kind)
	{
		echo "\tTracking $accountId ($kind):" . PHP_EOL;
		try
		{
			$key = "$accountId-$kind";
			$r = $cacheInstance->get($key);

			if($r === null || $r === true || DEBUG)
			{
				$result = submitTracking($accountId, $kind, $cacheInstance);
				if($result !== null)
				{
					// 3. If server told client to stop tracking but it's been 5 minutes, start tracking again by expiring the key
					if(!$cacheInstance->set($key, $result['data']['continueTracking'], TRACKING_MINUTES))
					{
						echo 'Failed to update cache!' . PHP_EOL;
					}
				}
				else
				{
					delog('An error occurred while trying to track!' . PHP_EOL);
				}
			}
			else
			{
				delog('Waiting...' . PHP_EOL);
			}
		}
		catch(Exception $e)
		{
			echo 'Error: ' . print_r($e, true);
		}
	}
}


/**
 * @param string $accountId Blizzard account id. Can be obtained at "https://www.blizzard.com/en-us/user".
 * @param string $esport Which esport to track. Valid arguments are "owl" and "contenders".
 * @return mixed|null Returns the "continueTracking" data or if an error occurred null.
 * @throws JsonException
 * @throws PhpfastcacheSimpleCacheException
 */
function submitTracking(string $accountId, string $esport = 'owl', Psr16Adapter $cacheInstance = null): mixed
{
	$esport = strtolower($esport);

	// While unlikely, IDs may change
	$validEsports = [
			'owl' => 'bltfed4276975b6d58a',
			'contenders' => 'blt942744e48c33cdc9'
	];

	if(!array_key_exists($esport, $validEsports))
	{
		throw new RuntimeException('Esport not found!');
	}

	$videoId = getVideoId($esport, $cacheInstance);
	if($videoId === null)
	{
//		delog('no vid id' . PHP_EOL);
		return null;
	}

	try
	{
		$client = new GuzzleHttp\Client();
		$resp = $client->post(API_ENDPOINT . "/sentinel-tracking/$esport", [
				'json' => [
						'accountId' => $accountId,
						'contentType' => 'live',
						'entryId' => $validEsports[$esport],
						'id_type' => 'battleNetId',
						'liveTest' => false,
						'locale' => 'en-us',
						'timestamp' => time(),
						'type' => 'video_player',
						'videoId' => $videoId,
				],
				'headers' => [
						'accept' => 'application/json',
						'content-type' => 'application/json',
						'origin' => 'https://overwatchleague.com',
						'x-origin' => 'overwatchleague.com',
						'referer' => 'https://overwatchleague.com/',
						'user-agent' => USER_AGENT,
				],
		]);

		$data = $resp->getBody()->getContents();
		echo "\t\t$data" . PHP_EOL;

		return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
	}
	catch(GuzzleException $e)
	{
		delog('Request Error: ' . $e->getMessage() . PHP_EOL);
		return null;
	}
}

/**
 * Gets the currently live OWL YouTube video id.
 * @param string $esport Which esport to get. Valid arguments are "owl" and "contenders".
 * @return string|null Returns the current video id.
 * @throws JsonException
 * @throws PhpfastcacheSimpleCacheException
 */
function getVideoId(string $esport = 'owl', Psr16Adapter $cacheInstance = null): string|null
{
	$esport = strtolower($esport);

	$validEsports = [
			'owl' => 'https://overwatchleague.com/en-us',
			'contenders' => 'https://overwatchleague.com/en-us/contenders'
	];

	if(!array_key_exists($esport, $validEsports))
	{
		throw new RuntimeException('Esport not found!');
	}

	$key = "$esport-videoId";
	$r = $cacheInstance?->get($key);
	if($r !== null)
	{
		delog("Using cached videoId: $r" . PHP_EOL);
		return $r;
	}

	$content = xpathFromContent(file_get_contents($validEsports[$esport]));
	$nextData = json_decode($content->query('//*[@id="__NEXT_DATA__"]')->item(0)->textContent, true, 512, JSON_THROW_ON_ERROR);

	// FIXME: Contenders always has a video even when not live
	$videoId = null;
	foreach($nextData['props']['pageProps']['blocks'] as $block)
	{
		if(!isset($block['videoPlayer']['video']['id']))
		{
			continue;
		}

		$videoId = $block['videoPlayer']['video']['id'];
		if(!$cacheInstance?->set($key, $videoId, VIDEO_MINUTES))
		{
			echo 'Failed to update cache!' . PHP_EOL;
		}
	}

	return $videoId;
}

/**
 * Checks if the OWL stream is live.
 * Docs: https://api.overwatchleague.com/docs/#tag/OWL/paths/~1live-match/get
 * @return bool Returns true if OWL stream is live.
 */
function shouldHeartbeat(): bool
{
	try
	{
		$client = new GuzzleHttp\Client();
		$resp = $client->get(API_ENDPOINT . '/content-types/match-ticker/?locale=en-us', [
				'headers' => [
						'x-origin' => 'overwatchleague.com',
						'referer' => 'https://overwatchleague.com/',
						'user-agent' => USER_AGENT,
				],
		]);
		$data = json_decode($resp->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

		if(isset($data['data'][0]['show']) && $data['data'][0]['show'] === false)
		{
			return false;
		}

		// Is any match in progress (live)?
		foreach($data['data'] as $item)
		{
			// Valid options are: PENDING, IN_PROGRESS
			if($item['status'] === 'IN_PROGRESS')
			{
				return true;
			}
		}
	}
	catch(GuzzleException $e)
	{
		delog('Request Error: ' . $e->getMessage() . PHP_EOL);
	}
	catch(JsonException $e)
	{
		delog('JSON  Error: ' . $e->getMessage() . PHP_EOL);
	}

	return false;
}