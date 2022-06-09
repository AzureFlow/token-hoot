<?php

/**
 * Print a debug log message.
 * @param string $msg The message.
 * @return void
 */
function delog(string $msg): void
{
	if(DEBUG)
	{
		echo "\033[1;36m" . "[*] $msg" . "\033[0m" . PHP_EOL;
	}
}

/**
 * Converts html to an XPath.
 * @param string $content The HTML content.
 * @return DOMXPath Returns the XPath result.
 */
function xpathFromContent(string $content): DOMXPath
{
	$doc = new DOMDocument();
	@$doc->loadHTML($content);
	return new DOMXPath($doc);
}

/**
 * Simple timestamp string.
 * @return string Returns a timestamp.
 */
function getTimestamp(): string
{
	$datetime = new DateTime();
	$datetime->setTimezone(new DateTimeZone('UTC'));

	return $datetime->format('M dS, Y - h:i:s A');
}