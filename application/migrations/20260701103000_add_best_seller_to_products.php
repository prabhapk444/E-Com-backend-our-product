<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_best_seller_to_products extends CI_Migration {

	public function up()
	{
		$this->db->query("ALTER TABLE `products` ADD COLUMN `best_seller` TINYINT(1) NOT NULL DEFAULT 0 AFTER `new_arrivals`");
	}

	public function down()
	{
		$this->db->query("ALTER TABLE `products` DROP COLUMN `best_seller`");
	}
}