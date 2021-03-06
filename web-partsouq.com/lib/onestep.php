<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;

use Illuminate\Database\Schema\Blueprint;
use GuzzleHttp\Client;
/**
  * 检测需要下载的批次并下载相应批次的列表页
  * @author xu
  * @copyright 2018/01/29
  */
class onestep{
	// 初始化所有数据表
	public static function initable()
	{
		// url_market表
		if(!Capsule::schema()->hasTable('url_market'))
		{
			Capsule::schema()->create('url_market', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('level')->nullable()->default(0);
			    $table->string('status')->nullable();
			});
			echo "table url_market create".PHP_EOL;
		}
		// url_car表
		if(!Capsule::schema()->hasTable('url_car'))
		{
			Capsule::schema()->create('url_car', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			});
			echo "table url_car create".PHP_EOL;
		}
		// url_part
		if(!Capsule::schema()->hasTable('url_part'))
		{
			Capsule::schema()->create('url_part', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('car_id')->nullable();
			});
			echo "table url_part create".PHP_EOL;
		}
		// url_pic
		if(!Capsule::schema()->hasTable('url_pic'))
		{
			Capsule::schema()->create('url_pic', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('md5_url')->unique();
			    $table->text('url')->nullable();
			    $table->string('status')->nullable();
			    $table->string('car_id')->nullable();
			});
			echo "table url_pic create".PHP_EOL;
		}
		// carparts
		if(!Capsule::schema()->hasTable('carparts'))
		{
			Capsule::schema()->create('carparts', function (Blueprint $table){
			    $table->increments('id')->unique();
			    $table->string('simple')->nullable();
			    $table->string('car_id')->nullable();
			    // 描述图片地址
			    $table->text('image')->nullable();
			    // 配件左侧介绍信息json格式存储 {1:msg1,2:msg2}
			    $table->longText('part_detail')->nullable();
			    // 页面网址
			    $table->text('url')->nullable();
			    // 页面网址md5数值用于防止重复
			    $table->string('url_md5')->unique();
			});
			echo "table carparts create".PHP_EOL;
		}

		// carinfo
		if(!Capsule::schema()->hasTable('carinfo'))
		{
			Capsule::schema()->create('carinfo', function (Blueprint $table){
				$table->increments('id')->unique();
				// 汽车所属类别
				$table->text('CatalogBrand')->nullable();
				$table->text('CatalogName')->nullable();
				$table->text('CatalogCode')->nullable();
				// 汽车栏目数据
				$table->text('Name')->nullable();
				$table->text('Transmission')->nullable();
				$table->text('SeriesCode')->nullable();
				$table->text('Engine')->nullable();
				$table->text('BodyStyle')->nullable();
				$table->text('Steering')->nullable();
				$table->text('Model')->nullable();
				$table->text('SeriesDescription')->nullable();
				$table->text('Doors')->nullable();
				$table->text('Country')->nullable();
				$table->text('Grade')->nullable();
				$table->text('Region')->nullable();
				$table->text('CountryDecode')->nullable();
				$table->text('Manufactured')->nullable();
				$table->text('ModelYearTo')->nullable();
				$table->text('OptionS')->nullable();
				$table->text('Family')->nullable();
				$table->text('VehicleCategory')->nullable();
				$table->text('ModelYearFrom')->nullable();
				$table->text('Market')->nullable();
				$table->text('Autoid')->nullable();
				$table->text('Description')->nullable();
				$table->text('ProdPeriod')->nullable();
				$table->text('CarLine')->nullable();
				$table->text('DestinationRegion')->nullable();
				$table->text('Datefrom')->nullable();
				$table->text('ModelYear')->nullable();
				$table->text('Drive')->nullable();
				$table->text('CatalogNo')->nullable();
				$table->text('VehicleClass')->nullable();
				$table->text('Aggregates')->nullable();
				$table->text('FrameS')->nullable();
				$table->text('Modification')->nullable();
				$table->text('VehicleType')->nullable();
				$table->text('Type')->nullable();
				// 汽车配件详情页链接
			    $table->text('url')->nullable();
			    $table->string('md5_url')->unique();
			    $table->string('status')->nullable();
			});
			echo "table carinfo create".PHP_EOL;
		}

	}
	// // 获取所有如下链接=>url_market
	// https://partsouq.com/en/catalog/genuine/locate?c=BMW
	public static function market()
	{
		// 解析首页
		$prefix = 'https://partsouq.com/en/catalog/genuine/filter?c=';
		$client = new Client();
		$config = array(
				'verify' => false,
				// 'proxy'=> "http://60.184.196.224:22914",
			);
		$response = $client->get('https://partsouq.com/catalog/genuine',$config);
		// 创建dom对象
		if($dom = HtmlDomParser::str_get_html($response->getBody()))
		{
			foreach($dom->find('tbody h4') as $article)
			{
				if(!$article->find("a",0))
				{
					// 排除<h4>Login</h4>按钮
					continue;
				}
				// 加限定只要Nissan的数据
				// if(!strpos($article->find("a",0)->href, 'Nissan'))
				// {
				// 	continue;
				// }

				// 获取当前品牌
				$href = explode("?c=", $article->find("a",0)->href);
				if(is_array($href))
				{
					$href = end($href);
				}
				else
				{
					continue;
				}
			    // 存储进去所有的&body
			    $temp = [
			    	'url' => $prefix.$href,
			    	'status' => 'wait',
			    	'md5_url' => md5($prefix.$href)
			    ];
			    $empty = Capsule::table('url_market')
			    	->where('md5_url',md5($prefix.$href))
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('url_market')->insert($temp);					    	
			    }
			}
			echo 'url_market analyse completed!'.PHP_EOL;
			// 清理内存防止内存泄漏
			$dom-> clear(); 
		}
		else
		{
			exit('net error!');
		}
	}
	

}