<?php
/**
 * The MIT License (MIT)
 *
 * Webzash - Easy to use web based double entry accounting software
 *
 * Copyright (c) 2014 Prashant Shah <pshah.mumbai@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

App::uses('WebzashAppController', 'Webzash.Controller');
App::uses('GroupTree', 'Webzash.Lib');

/**
 * Webzash Plugin Ledgers Controller
 *
 * @package Webzash
 * @subpackage Webzash.controllers
 */
class ApiledgersController extends WebzashAppController {

	public $uses = array('Webzash.Ledger', 'Webzash.Group', 'Webzash.Entryitem',
		'Webzash.Log');

	public $components = array('RequestHandler');

/**
 * index method
 *
 * @return void
 */
	public function index() {
		//http://127.0.0.1:8080/webzash/
		//http://127.0.0.1:8080/webzash/apiledgers/index.json
/*
1. custom header

2. apache_request_headers();

3.
  <IfModule mod_rewrite.c>
     SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
     RewriteEngine on
     RewriteRule    ^$ app/webroot/    [L]
     RewriteRule    (.*) app/webroot/$1 [L]
  </IfModule>
  $this->request->header('Authorization')

*/
        $this->set(array(
            'ledgers' => array('ok'),
            '_serialize' => array('recipes')
        ));
		debug($this->request->header('Authorization'));
	}

	public function beforeFilter() {
		parent::beforeFilter();

		$this->Auth->allow('index');
	}

	/* Authorization check */
	public function isAuthorized($user) {
		return parent::isAuthorized($user);
	}
}
