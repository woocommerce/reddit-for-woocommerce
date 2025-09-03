<?php

function r4w_setup_globals() {
	$_SERVER['HTTP_REFERER']    = 'https://example.com/cart';
	$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 user-agent';
	$_SERVER['REMOTE_ADDR']     = '192.168.4.65';
	$_COOKIE['_rdt_uuid']       = 'test-uuid';
	$_COOKIE['rdtCid']          = 'rdt-cid-uuid';
}

function r4w_destroy_globals() {
	$_SERVER['HTTP_REFERER']    = '';
	$_SERVER['HTTP_USER_AGENT'] = '';
	$_SERVER['REMOTE_ADDR']     = '';
	$_COOKIE['_rdt_uuid']       = '';
	$_COOKIE['rdtCid']          = '';
}