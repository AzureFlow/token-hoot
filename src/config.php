<?php

define('ROOT', getenv('OWL_DATA') !== false ? getenv('OWL_DATA') : __DIR__);
const TRACKING_MINUTES = 60 * 5;
const VIDEO_MINUTES = 60 * 30;
const DEBUG = false;
const SERVICES = ['owl', 'contenders'];
// Must include leading slash
const API_ENDPOINT = 'https://pk0yccosw3.execute-api.us-east-2.amazonaws.com/production/v2';
const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36';
const USER_AGENT_MOBILE = 'OverwatchLeague/3.5.0 (com.blizzard.owl; build:22; iOS 15.2.0) Alamofire/5.1.0';