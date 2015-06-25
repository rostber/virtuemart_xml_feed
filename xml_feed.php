<?
error_reporting(E_ALL);

$secret = 'k59fkj29gja'

Class export
{
	var $categories_output;
	var $products_output;
	
	function __construct()
	{
		header("Content-Type: text/xml");
		
		include_once dirname(__FILE__) . '/defines.php';
		define('_JEXEC', 1);
		define('DS', DIRECTORY_SEPARATOR);
		if (!defined('_JDEFINES')) {
			define('JPATH_BASE', dirname(__FILE__));
			require_once JPATH_BASE.'/includes/defines.php';
		}
		require_once JPATH_BASE.'/includes/framework.php';
		
		$app = JFactory::getApplication('site');
		$app->initialise();
		$config = new JConfig();
		$this->prefix = $config->dbprefix;
		$this->db = JFactory::getDbo();

		$this->get_groups_on_parent(0, 0, 'http://'.$_SERVER['HTTP_HOST']);
	}

	public function get_groups_on_parent($parent_id = 0, $level = 0, $path = '')
	{
		$this->db->setQuery('SELECT vcc.id, vcl.category_name, vcl.slug, vcc.category_parent_id FROM '.$this->prefix.'virtuemart_categories vc INNER JOIN '.$this->prefix.'virtuemart_category_categories vcc ON (vcc.id = vc.virtuemart_category_id) INNER JOIN '.$this->prefix.'virtuemart_categories_ru_ru vcl ON (vcl.virtuemart_category_id = vc.virtuemart_category_id) WHERE vcc.category_parent_id = '.$parent_id);
		$res = $this->db->loadObjectList();
		if ($res) foreach($res as $v)
		{
			$new_path = $path;//$new_path = $path.'/'.$v->slug;
			$is_products = $this->print_products($v->id, $v->category_name, $level, $new_path);
			if ($is_products)
			{
				$str = '      <category id="'.$v->id.'" parentId="'.$parent_id.'">'.htmlspecialchars($v->category_name)."</category>\n";
				$this->categories_output = $this->categories_output.$str;
			}
			$this->get_groups_on_parent($v->id, $level + 1, $new_path);
		}
	}

	public function print_products($category_id, $title, $level, $path)
	{
		$str = '';
		$this->db->setQuery('SELECT vpc.id, vpl.product_name, vpl.slug, vpp.product_price FROM '.$this->prefix.'virtuemart_product_categories vpc INNER JOIN '.$this->prefix.'virtuemart_products_ru_ru vpl ON (vpc.id = vpl.virtuemart_product_id) INNER JOIN '.$this->prefix.'virtuemart_product_prices vpp ON (vpc.id = vpp.virtuemart_product_id) WHERE vpc.virtuemart_category_id = '.$category_id);
		$res = $this->db->loadObjectList();
		if ($res) foreach($res as $product)
		{
			$str = $str.'      <offer id="'.$product->id.'" available="true">'."\n";
			$str = $str.'        <url>'.$path.'/'.$product->slug."</url>\n";
			$str = $str.'        <price>'.$product->product_price."</price>\n";
			$str = $str."        <currencyId>RUB</currencyId>\n";
			$str = $str.'        <categoryId>'.$category_id."</categoryId>\n";
			$str = $str.'        <name>'.htmlspecialchars($product->product_name)."</name>\n";
			$str = $str."      </offer>\n";
		}
		$this->products_output = $this->products_output.$str;
		return count($res);
	}

}

if ($_GET['code'] != $secret) exit;
$export = new export();

?>
<?='<'?>?xml version="1.0" encoding="UTF-8"?<?='>'?><!DOCTYPE yml_catalog SYSTEM "shops.dtd">
<yml_catalog date="<?=date("d.m.Y H:i:s")?>">
  <shop>
    <url>http://<?=$_SERVER['HTTP_HOST']?></url>
    <platform>Custom</platform>
    <currencies>
      <currency id="RUB" rate="1"/>
    </currencies>
    <categories>
<?=$export->categories_output?>
    </categories>
    <offers>
<?=$export->products_output?>
    </offers>
  </shop>
</yml_catalog>