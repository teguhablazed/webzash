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
 * Webzash Plugin API Entries Controller
 *
 * @package Webzash
 * @subpackage Webzash.controllers
 */
class ApientriesController extends WebzashAppController {

	public $uses = array('Webzash.Entry', 'Webzash.Group', 'Webzash.Ledger',
		'Webzash.Entrytype', 'Webzash.Entryitem', 'Webzash.Tag', 'Webzash.Log');

	public $components = array('RequestHandler');

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->autoRender = false;

		$conditions = array();

		/* Filter by entry type */
		if (isset($this->passedArgs['show'])) {
			$entrytype = $this->Entrytype->find('first', array('conditions' => array('Entrytype.label' => $this->passedArgs['show'])));
			if (!$entrytype) {
				return json_encode(array(
					'status' => 'ERROR',
					'msg' => __d('webzash', 'Entry type not found.')
				));
			}

			$conditions['Entry.entrytype_id'] = $entrytype['Entrytype']['id'];
		}

		/* Filter by tag */
		if (isset($this->passedArgs['tag'])) {
			$conditions['Entry.tag_id'] = $this->passedArgs['tag'];
		}

		/* Setup pagination */
		$this->CustomPaginator->settings = array(
			'Entry' => array(
				'conditions' => $conditions,
				'order' => array('Entry.date' => 'desc'),
			)
		);

		if (empty($this->passedArgs['show'])) {
			$this->request->data['Entry']['show'] = '0';
		} else {
			$this->request->data['Entry']['show'] = $this->passedArgs['show'];
		}

		return json_encode($this->CustomPaginator->paginate('Entry'));
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
