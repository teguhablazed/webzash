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

		/* Check Request Type */
		if (!$this->request->is('get')) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Method not allowed.')
			));
		}

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

		// Sanitizing the data for json output
		$entries = array();
		foreach ($this->CustomPaginator->paginate('Entry') as $key => $value) {
			$entries[] = $value['Entry'];
		}

		return json_encode(array(
			'status' => 'SUCCESS',
			'data' => array('entries' => $entries)
		));
	}

/**
 * view method
 *
 * @param string $entrytypeLabel
 * @param string $id
 * @return void
 */
	public function view($entrytypeLabel = null, $id = null) {
		$this->autoRender = false;

		/* Check Request Type */
		if (!$this->request->is('get')) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Method not allowed.')
			));
		}

		/* Check for valid entry type */
		if (!$entrytypeLabel) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry type not specified.')
			));
		}
		$entrytype = $this->Entrytype->find('first', array('conditions' => array('Entrytype.label' => $entrytypeLabel)));
		if (!$entrytype) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry type not found.')
			));
		}
		$this->set('entrytype', $entrytype);

		/* Check for valid entry id */
		if (empty($id)) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry not specified.')
			));
		}
		$entry = $this->Entry->findById($id);
		if (!$entry) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry not found.')
			));
		}

		/* Initial data */
		$curEntryitems = array();
		$curEntryitemsData = $this->Entryitem->find('all', array(
			'conditions' => array('Entryitem.entry_id' => $id),
		));
		foreach ($curEntryitemsData as $row => $data) {
			if ($data['Entryitem']['dc'] == 'D') {
				$curEntryitems[$row] = array(
					'dc' => $data['Entryitem']['dc'],
					'ledger_id' => $data['Entryitem']['ledger_id'],
					'ledger_name' => $this->Ledger->getName($data['Entryitem']['ledger_id']),
					'dr_amount' => $data['Entryitem']['amount'],
					'cr_amount' => '',
				);
			} else {
				$curEntryitems[$row] = array(
					'dc' => $data['Entryitem']['dc'],
					'ledger_id' => $data['Entryitem']['ledger_id'],
					'ledger_name' => $this->Ledger->getName($data['Entryitem']['ledger_id']),
					'dr_amount' => '',
					'cr_amount' => $data['Entryitem']['amount'],
				);
			}
		}
		$this->set('curEntryitems', $curEntryitems);

		/* Pass varaibles to view which are used in Helpers */
		$this->set('allTags', $this->Tag->fetchAll());

		$this->set('entry', $entry);

		return json_encode(array(
			'status' => 'SUCCESS',
			'data' => array(
				'entry' => $entry['Entry'],
				'entry_items' => $curEntryitems
			),
		));
	}

/**
 * delete method
 *
 * @throws MethodNotAllowedException
 * @param string $entrytypeLabel
 * @param string $id
 * @return void
 */
	public function delete($entrytypeLabel = null, $id = null) {
		$this->autoRender = false;

		/* Check Request Type */
		if (!$this->request->is('delete')) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Method not allowed.')
			));
		}

		/* Check for valid entry type */
		if (empty($entrytypeLabel)) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry type not specified.')
			));
		}
		$entrytype = $this->Entrytype->find('first', array('conditions' => array('Entrytype.label' => $entrytypeLabel)));
		if (!$entrytype) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry type not found.')
			));
		}

		/* Check if valid id */
		if (empty($id)) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry not specified.')
			));
		}

		/* Check if entry exists */
		$entry = $this->Entry->findById($id);
		if (!$entry) {
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Entry not found.')
			));
		}

		$ds = $this->Entry->getDataSource();
		$ds->begin();

		/* Delete entry items */
		if (!$this->Entryitem->deleteAll(array('Entryitem.entry_id' => $id))) {
			$ds->rollback();
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Failed to delete entry items.')
			));
		}

		/* Delete entry */
		if (!$this->Entry->delete($id)) {
			$ds->rollback();
			return json_encode(array(
				'status' => 'ERROR',
				'msg' => __d('webzash', 'Failed to delete entry.')
			));
		}

		$entryNumber = h(toEntryNumber($entry['Entry']['number'], $entrytype['Entrytype']['id']));

		$this->Log->add('Deleted ' . $entrytype['Entrytype']['name'] . ' entry numbered ' . $entryNumber, 1);
		$ds->commit();

		return json_encode(array(
			'status' => 'SUCCESS',
			'msg' => __d('webzash', 'Entry deleted.')
		));
	}

	public function beforeFilter() {
		parent::beforeFilter();

		/* Disable csrf check for API methods */
		$this->Security->csrfCheck = false;

		$this->Auth->allow('index');
		$this->Auth->allow('view');
		$this->Auth->allow('delete');
	}

	/* Authorization check */
	public function isAuthorized($user) {
		return parent::isAuthorized($user);
	}
}
