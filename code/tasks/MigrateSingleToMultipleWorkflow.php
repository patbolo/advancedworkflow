<?php

class MigrateSingleToMultipleWorkflow extends BuildTask {
	
	/**
	 * @var bool $enabled If set to FALSE, keep it from showing in the list
	 * and from being executable through URL or CLI.
	 */
	protected $enabled = true;
	
	/**
	 * @var string $title Shown in the overview on the {@link TaskRunner}
	 * HTML or CLI interface. Should be short and concise, no HTML allowed.
	 */
	protected $title = "Migrate workflows";
	
	/**
	 * @var string $description Describe the implications the task has,
	 * and the changes it makes. Accepts HTML formatting.
	 */
	protected $description = 'Migrate single workflow to multiple workflows';
	
	/**
	 * 
	 * @param SS_HTTPRequest $request
	 * @return type
	 */
	public function run($request) {
		$conn = DB::getConn();
		
		$siteTree = singleton('SiteTree');
		
		foreach($siteTree->getClassAncestry() as $ancestor) {
			if(DataObject::has_own_table($ancestor)) {
				$ancestry[] = $ancestor;
			}
		}
		
		$tableName = ($ancestry[0]);
		
		if($conn instanceof MySQLDatabase) {
			$query = 'SHOW columns FROM "'.$tableName.'" WHERE field=\'WorkflowDefinitionID\'';
		}

		// You, we're still running on the good old version
		$result = DB::query($query);
		
		if(!$result->numRecords()) {
			echo 'No migration needed'.PHP_EOL;
			return;
		}
		
		if($conn instanceof MySQLDatabase) {
			$query = 'SELECT "ID", "ClassName", "URLSegment", "WorkflowDefinitionID" FROM "SiteTree" WHERE "WorkflowDefinitionID" IS NOT NULL AND "WorkflowDefinitionID" != \'0\';';
		}
		
		$pagesNeedingMigration = DB::query($query);
		if(!$pagesNeedingMigration->numRecords()) {
			echo 'No migration needed'.PHP_EOL;
			return;
		}
		
		foreach($pagesNeedingMigration as $row) {
			$page = DataObject::get_by_id($row['ClassName'], $row['ID']);
			if(!$page) {
				continue;
			}
			
			$wfDefinitions = $page->WorkflowDefinitions();
			$wfDefinitions->removeAll();
			$wfDefinitions->add($row['WorkflowDefinitionID']);
			
			echo 'Updated "'.$page->Link().'" to use worflow with ID '.$row['WorkflowDefinitionID'].PHP_EOL;
			
			if($conn instanceof MySQLDatabase) {
				$query = 'UPDATE "SiteTree" SET "WorkflowDefinitionID" = 0 WHERE "ID" = '.$row['ID'];
			}
			
			DB::query($query);
			
			
			
			
		}
	}
}
