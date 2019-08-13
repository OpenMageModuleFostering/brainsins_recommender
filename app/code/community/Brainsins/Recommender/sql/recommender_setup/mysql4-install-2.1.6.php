<?php
$installer = $this;
try{
	$installer->startSetup();
	$installer->getConnection()->addColumn(
		$this->getTable('sales_flat_quote'),
	    'brainsins_qhash',
	    array(
	            'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
	            'length' => 255,
	            'nullable' => false,
	            'default' => '',
	            'comment' => 'Random hash to identify quote ID'
	        ) 
	    );
	$installer->endSetup();
}catch(Eception $e){
	error_log($e);
}

?>