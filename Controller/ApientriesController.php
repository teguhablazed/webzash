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
 * add method
 *
 * @param string $entrytypeLabel
 * @return void
 */
	public function add($entrytypeLabel = null) {
		$this->autoRender = false;

		/* Check Request Type */
		if (!$this->request->is('post')) {
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
				'msg' => __d('webzash', 'Entry not found.')
			));
		}
		//$this->set('entrytype', $entrytype);
		//
		///* Ledger selection */
		//$ledgers = new LedgerTree();
		//$ledgers->Group = &$this->Group;
		//$ledgers->Ledger = &$this->Ledger;
		//$ledgers->current_id = -1;
		//$ledgers->restriction_bankcash = $entrytype['Entrytype']['restriction_bankcash'];
		//$ledgers->build(0);
		//$ledgers->toList($ledgers, -1);
		//$ledgers_disabled = array();
		//foreach ($ledgers->ledgerList as $row => $data) {
		//	if ($row < 0) {
		//		$ledgers_disabled[] = $row;
		//	}
		//}
		//$this->set('ledger_options', $ledgers->ledgerList);
		//$this->set('ledgers_disabled', $ledgers_disabled);
		//
		///* Initial data */
		//if ($this->request->is('post')) {
		//	$curEntryitems = array();
		//	foreach ($this->request->data['Entryitem'] as $row => $entryitem) {
		//		$curEntryitems[$row] = array(
		//			'dc' => $entryitem['dc'],
		//			'ledger_id' => $entryitem['ledger_id'],
		//			'dr_amount' => isset($entryitem['dr_amount']) ? $entryitem['dr_amount'] : '',
		//			'cr_amount' => isset($entryitem['cr_amount']) ? $entryitem['cr_amount'] : '',
		//		);
		//	}
		//	$this->set('curEntryitems', $curEntryitems);
		//} else {
		//	$curEntryitems = array();
		//	if ($entrytype['Entrytype']['restriction_bankcash'] == 3) {
		//		/* Special case if atleast one Bank or Cash on credit side (3) then 1st item is Cr */
		//		$curEntryitems[0] = array('dc' => 'C');
		//		$curEntryitems[1] = array('dc' => 'D');
		//	} else {
		//		/* Otherwise 1st item is Dr */
		//		$curEntryitems[0] = array('dc' => 'D');
		//		$curEntryitems[1] = array('dc' => 'C');
		//	}
		//	$curEntryitems[2] = array('dc' => 'D');
		//	$curEntryitems[3] = array('dc' => 'D');
		//	$curEntryitems[4] = array('dc' => 'D');
		//	$this->set('curEntryitems', $curEntryitems);
		//}

		/* On POST */
		if ($this->request->is('post')) {
			if (!empty($this->request->data)) {

				/***************************************************************************/
				/*********************************** ENTRY *********************************/
				/***************************************************************************/

				$entrydata = null;

				/* Entry id */
				unset($this->request->data['Entry']['id']);

				/***** Check and update entry number ******/
				if ($entrytype['Entrytype']['numbering'] == 1) {
					/* Auto */
					if (empty($this->request->data['Entry']['number'])) {
						$entrydata['Entry']['number'] = $this->Entry->nextNumber($entrytype['Entrytype']['id']);
					} else {
						$entrydata['Entry']['number'] = $this->request->data['Entry']['number'];
					}
				} else if ($entrytype['Entrytype']['numbering'] == 2) {
					/* Manual + Required */
					if (empty($this->request->data['Entry']['number'])) {
						return json_encode(array(
							'status' => 'ERROR',
							'msg' => __d('webzash', 'Entry number cannot be empty.')
						));
					} else {
						$entrydata['Entry']['number'] = $this->request->data['Entry']['number'];
					}
				} else {
					/* Manual + Optional */
					$entrydata['Entry']['number'] = $this->request->data['Entry']['number'];
				}

				/****** Check entry type *****/
				$entrydata['Entry']['entrytype_id'] = $entrytype['Entrytype']['id'];

				/****** Check tag ******/
				if (empty($this->request->data['Entry']['tag_id'])) {
					$entrydata['Entry']['tag_id'] = null;
				} else {
					$entrydata['Entry']['tag_id'] = $this->request->data['Entry']['tag_id'];
				}

				/***** Narration *****/
				if (empty($this->request->data['Entry']['narration'])) {
					return json_encode(array(
						'status' => 'ERROR',
						'msg' => __d('webzash', 'Narration cannot be empty.')
					));
				}
				$entrydata['Entry']['narration'] = $this->request->data['Entry']['narration'];

				/***** Date *****/
				if (empty($this->request->data['Entry']['date'])) {
					return json_encode(array(
						'status' => 'ERROR',
						'msg' => __d('webzash', 'Date cannot be empty.')
					));
				}
				$entrydata['Entry']['date'] = dateToSql($this->request->data['Entry']['date']);

				/***************************************************************************/
				/***************************** ENTRY ITEMS *********************************/
				/***************************************************************************/

				/* Check ledger restriction */
				if (empty($this->request->data['Entryitem'])) {
					return json_encode(array(
						'status' => 'ERROR',
						'msg' => __d('webzash', 'Entry items cannot be empty.')
					));
				}
				$dc_valid = false;
				foreach ($this->request->data['Entryitem'] as $row => $entryitem) {
					if ($entryitem['ledger_id'] <= 0) {
						continue;
					}
					$ledger = $this->Ledger->findById($entryitem['ledger_id']);
					if (!$ledger) {
						return json_encode(array(
							'status' => 'ERROR',
							'msg' => __d('webzash', 'Invalid ledger selected.')
						));
					}

					if ($entrytype['Entrytype']['restriction_bankcash'] == 4) {
						if ($ledger['Ledger']['type'] != 1) {
							return json_encode(array(
								'status' => 'ERROR',
								'msg' => __d('webzash', 'Only bank or cash ledgers are allowed for this entry type.')
							));
						}
					}
					if ($entrytype['Entrytype']['restriction_bankcash'] == 5) {
						if ($ledger['Ledger']['type'] == 1) {
							return json_encode(array(
								'status' => 'ERROR',
								'msg' => __d('webzash', 'Bank or cash ledgers are not allowed for this entry type.')
							));
						}
					}

					if ($entryitem['dc'] == 'D') {
						if ($entrytype['Entrytype']['restriction_bankcash'] == 2) {
							if ($ledger['Ledger']['type'] == 1) {
								$dc_valid = true;
							}
						}
					} else if ($entryitem['dc'] == 'C') {
						if ($entrytype['Entrytype']['restriction_bankcash'] == 3) {
							if ($ledger['Ledger']['type'] == 1) {
								$dc_valid = true;
							}
						}
					}
				}
				if ($entrytype['Entrytype']['restriction_bankcash'] == 2) {
					if (!$dc_valid) {
						return json_encode(array(
							'status' => 'ERROR',
							'msg' => __d('webzash', 'Atleast one bank or cash ledger has to be on debit side for this entry type.')
						));
					}
				}
				if ($entrytype['Entrytype']['restriction_bankcash'] == 3) {
					if (!$dc_valid) {
						return json_encode(array(
							'status' => 'ERROR',
							'msg' => __d('webzash', 'Atleast one bank or cash ledger has to be on credit side for this entry type.')
						));
					}
				}

				$dr_total = 0;
				$cr_total = 0;

				/* Check equality of debit and credit total */
				foreach ($this->request->data['Entryitem'] as $row => $entryitem) {
					if ($entryitem['ledger_id'] <= 0) {
						continue;
					}

					if ($entryitem['dc'] == 'D') {
						if ($entryitem['dr_amount'] <= 0) {
							return json_encode(array(
								'status' => 'ERROR',
								'msg' => __d('webzash', 'Invalid amount specified. Amount cannot be negative or zero.')
							));
						}
						if (countDecimal($entryitem['dr_amount']) > Configure::read('Account.decimal_places')) {
							return json_encode(array(
								'status' => 'ERROR',
								'msg' => __d('webzash', 'Invalid amount specified. Maximum %s decimal places allowed.', Configure::read('Account.decimal_places'))
							));
						}
						$dr_total = calculate($dr_total, $entryitem['dr_amount'], '+');
					} else if ($entryitem['dc'] == 'C') {
						if ($entryitem['cr_amount'] <= 0) {
							return json_encode(array(
								'status' => 'ERROR',
								'msg' => __d('webzash', 'Invalid amount specified. Amount cannot be negative or zero.')
							));
						}
						if (countDecimal($entryitem['cr_amount']) > Configure::read('Account.decimal_places')) {
							return json_encode(array(
								'status' => 'ERROR',
								'msg' => __d('webzash', 'Invalid amount specified. Maximum %s decimal places allowed.', Configure::read('Account.decimal_places'))
							));
						}
						$cr_total = calculate($cr_total, $entryitem['cr_amount'], '+');
					} else {
						return json_encode(array(
							'status' => 'ERROR',
							'msg' => __d('webzash', 'Invalid Dr/Cr option selected.')
						));
					}
				}
				if (calculate($dr_total, $cr_total, '!=')) {
					return json_encode(array(
						'status' => 'ERROR',
						'msg' => __d('webzash', 'Debit and Credit total do not match.')
					));
				}

				$entrydata['Entry']['dr_total'] = $dr_total;
				$entrydata['Entry']['cr_total'] = $cr_total;

				/* Add item to entryitemdata array if everything is ok */
				$entryitemdata = array();
				foreach ($this->request->data['Entryitem'] as $row => $entryitem) {
					if ($entryitem['ledger_id'] <= 0) {
						continue;
					}
					if ($entryitem['dc'] == 'D') {
						$entryitemdata[] = array(
							'Entryitem' => array(
								'dc' => $entryitem['dc'],
								'ledger_id' => $entryitem['ledger_id'],
								'amount' => $entryitem['dr_amount'],
							)
						);
					} else {
						$entryitemdata[] = array(
							'Entryitem' => array(
								'dc' => $entryitem['dc'],
								'ledger_id' => $entryitem['ledger_id'],
								'amount' => $entryitem['cr_amount'],
							)
						);
					}
				}

				/* Save entry */
				$ds = $this->Entry->getDataSource();
				$ds->begin();

				$this->Entry->create();
				if ($this->Entry->save($entrydata)) {
					/* Save entry items */
					foreach ($entryitemdata as $row => $itemdata) {
						$itemdata['Entryitem']['entry_id'] = $this->Entry->id;
						$this->Entryitem->create();
						if (!$this->Entryitem->save($itemdata)) {
							foreach ($this->Entryitem->validationErrors as $field => $msg) {
								$errmsg = $msg[0];
								break;
							}
							$ds->rollback();
							return json_encode(array(
								'status' => 'ERROR',
								'msg' => __d('webzash', 'Failed to save entry ledgers. Error is : %s', $errmsg)
							));
						}
					}

					$tempentry = $this->Entry->read(null, $this->Entry->id);
					if (!$tempentry) {
						$ds->rollback();
						return json_encode(array(
							'status' => 'ERROR',
							'msg' => __d('webzash', 'Failed to create entry.')
						));
					}
					$entryNumber = h(toEntryNumber(
						$tempentry['Entry']['number'],
						$entrytype['Entrytype']['id']
					));

					$this->Log->add('Added ' . $entrytype['Entrytype']['name'] . ' entry numbered ' . $entryNumber, 1);
					$ds->commit();

					return json_encode(array(
						'status' => 'SUCCESS',
						'msg' => __d('webzash',
							'%s entry numbered %s created.',
							$entrytype['Entrytype']['name'],
							$entryNumber),
						'id' => $entryNumber
					));
				} else {
					foreach ($this->Entry->validationErrors as $field => $msg) {
						$errmsg = $msg[0];
						break;
					}
					$ds->rollback();
					return json_encode(array(
						'status' => 'ERROR',
						'msg' => __d('webzash', 'Failed to create entry. Error is : %s', $errmsg)
					));
				}
			} else {
				return json_encode(array(
					'status' => 'ERROR',
					'msg' => __d('webzash', 'No data.')
				));
			}
		}
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
		$this->Security->validatePost = false;

		$this->Auth->allow('index');
		$this->Auth->allow('view');
		$this->Auth->allow('add');
		$this->Auth->allow('delete');
	}

	/* Authorization check */
	public function isAuthorized($user) {
		return parent::isAuthorized($user);
	}
}
