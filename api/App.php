<?php
if(class_exists('Extension_DevblocksEventAction')):
class VaAction_CreateAsset extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$event = $trigger->getEvent();
		$values_to_contexts = $event->getValuesContexts($trigger);
		$tpl->assign('values_to_contexts', $values_to_contexts);
		
		// Custom fields
		DevblocksEventHelper::renderActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_ASSET, $tpl);
		
		// Template
		$tpl->display('devblocks:cerberusweb.assets::events/action_create_asset.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();

		$out = null;
		
		@$name = $tpl_builder->build($params['name'], $dict);
		
		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		if(empty($name))
			return "[ERROR] Name is required.";
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		$out = sprintf(">>> Creating asset: %s\n", $name);
		
		// Custom fields
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetCustomFields($params, $dict);
		
		$out .= "\n";
		
		// Comment content
		if(!empty($comment)) {
			$out .= sprintf(">>> Writing comment on asset\n\n".
				"%s\n\n",
				$comment
			);
			
			if(!empty($notify_worker_ids) && is_array($notify_worker_ids)) {
				$out .= ">>> Notifying\n";
				foreach($notify_worker_ids as $worker_id) {
					if(null != ($worker = DAO_Worker::get($worker_id))) {
						$out .= ' * ' . $worker->getName() . "\n";
					}
				}
				$out .= "\n";
			}
		}
		
		// Set object variable
		$out .= DevblocksEventHelper::simulateActionCreateRecordSetVariable($params, $dict);
		
		// Run in simulator
		@$run_in_simulator = !empty($params['run_in_simulator']);
		if($run_in_simulator) {
			$this->run($token, $trigger, $params, $dict);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		
		@$name = $tpl_builder->build($params['name'], $dict);

		@$notify_worker_ids = DevblocksPlatform::importVar($params['notify_worker_id'],'array',array());
		$notify_worker_ids = DevblocksEventHelper::mergeWorkerVars($notify_worker_ids, $dict);
		
		$comment = $tpl_builder->build($params['comment'], $dict);
		
		if(empty($name))
			return;
		
		$fields = array(
			DAO_Asset::NAME => $name,
		);
			
		if(false == ($asset_id = DAO_Asset::create($fields)))
			return;
		
		// Custom fields
		DevblocksEventHelper::runActionCreateRecordSetCustomFields(CerberusContexts::CONTEXT_ASSET, $asset_id, $params, $dict);
		
		// Comment content
		if(!empty($comment)) {
			$fields = array(
				DAO_Comment::OWNER_CONTEXT => CerberusContexts::CONTEXT_BOT,
				DAO_Comment::OWNER_CONTEXT_ID => $trigger->bot_id,
				DAO_Comment::COMMENT => $comment,
				DAO_Comment::CONTEXT => CerberusContexts::CONTEXT_ASSET,
				DAO_Comment::CONTEXT_ID => $asset_id,
				DAO_Comment::CREATED => time(),
			);
			DAO_Comment::create($fields, $notify_worker_ids);
		}

		// Set object variable
		DevblocksEventHelper::runActionCreateRecordSetVariable(CerberusContexts::CONTEXT_ASSET, $asset_id, $params, $dict);
	}
	
};
endif;