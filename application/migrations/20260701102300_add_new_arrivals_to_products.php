<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_new_arrivals_to_products extends CI_Migration {

	public function up()
	{
		$this->db->query("ALTER TABLE `products` ADD COLUMN `new_arrivals` TINYINT(1) NOT NULL DEFAULT 0 AFTER `featured`");
	}

	public function down()
	{
		$this->db->query("ALTER TABLE `products` DROP COLUMN `new_arrivals`");
	}
}