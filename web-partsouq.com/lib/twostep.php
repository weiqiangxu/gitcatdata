<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
// 多进程下载器
use Huluo\Extend\Gather;

use Illuminate\Database\Schema\Blueprint;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class twostep{

	// 车系=》车
	public static function car()
	{
		// 下载所有的model页面
		Capsule::table('url_model')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_model', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'url_model/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$client = new Client();
		    		// 注册异步请求
					$client->getAsync(html_entity_decode($data->url),['verify' => false])->then(
					    function (ResponseInterface $res) use ($file, $data)
					    {
							if($res->getStatusCode()== 200)
				    		{
				    			// 保存文件
					            file_put_contents($file,$res->getBody());
					            // 命令行执行时候不需要经过apache直接输出在窗口
					            echo 'url_model '.$data->id.'.html'." download successful!\r\n";
				    		}
				    		if(file_exists($file))
					    	{
					            // 更改SQL语句
					            Capsule::table('url_model')
							            ->where('id', $data->id)
							            ->update(['status' =>'completed']);
					    	}
					    },
					    function (RequestException $e) {
					        echo $e->getMessage() . "\r\n";
					        echo $e->getRequest()->getMethod(). "\r\n";
					    }
					)->wait();
		    	}
		    }
		});

		// 获取所有的车连接
		Capsule::table('url_model')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	// 保存文件名
		    	$file = PROJECT_APP_DOWN.'url_model/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取brand页面所有的model
						foreach($dom->find('.search-result-vin tr') as $tr)
						{
							if(!$tr->find("a",0))
							{
								continue;
							}
						    // 存储进去所有的&url_model
						    $temp = [
						    	'url' => $prefix.$tr->find("a",0)->href,
						    	'md5_url' => md5($prefix.$tr->find("a",0)->href),
						    	'status' => 'wait',
						    ];
						    $empty = Capsule::table('url_car')
						    	->where('md5_url',md5($prefix.$tr->find("a",0)->href))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_car')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_model')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_model '.$data->id.'.html'."  analyse successful!\r\n";
					}
		    	}
		    }
		});
	}

	// 车 =》 零件
	public static function part()
	{
		// 下载所有的model页面
		Capsule::table('url_car')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_car', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'url_car/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$client = new Client();
		    		// 注册异步请求
					$client->getAsync(html_entity_decode($data->url),['verify' => false])->then(
					    function (ResponseInterface $res) use ($file, $data)
					    {
							if($res->getStatusCode()== 200)
				    		{
				    			// 保存文件
					            file_put_contents($file,$res->getBody());
					            // 命令行执行时候不需要经过apache直接输出在窗口
					            echo 'url_car '.$data->id.'.html'." download successful!\r\n";
				    		}
				    		if(file_exists($file))
					    	{
					            // 更改SQL语句
					            Capsule::table('url_car')
							            ->where('id', $data->id)
							            ->update(['status' =>'completed']);
					    	}
					    },
					    function (RequestException $e) {
					        echo $e->getMessage() . "\r\n";
					        echo $e->getRequest()->getMethod(). "\r\n";
					    }
					)->wait();
		    	}
		    }
		});

		// 获取所有的车连接
		Capsule::table('url_car')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'url_car/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取brand页面所有的model
						foreach($dom->find('.list-unstyled a') as $a)
						{
						    // 存储进去所有的&model
						    $temp = [
						    	'url' => $prefix.$a->href,
						    	'status' => 'wait',
						    	'md5_url' => md5($prefix.$a->href)
						    ];
						    $empty = Capsule::table('url_part')
						    	->where('md5_url',md5($prefix.$a->href))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_part')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_car')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_car '.$data->id.'.html'."  analyse successful!\r\n";
					}
		    	}
		    }
		});


		// 将所有part默认页面移动过去
		Capsule::table('url_car')->where('status','readed')->orderBy('id')->chunk(20,function($datas){
			foreach ($datas as $data)
			{
				$temp = [
			    	'url' => $data->url,
			    	'status' => 'wait',
			    	'md5_url' => md5($data->url)
			    ];
			    $empty = Capsule::table('url_part')
			    	->where('md5_url',md5($data->url))
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('url_part')->insert($temp);
			    	echo "url_car ".$data->id." moved!\r\n";
			    	// 更改SQL语句
		            Capsule::table('url_car')
				            ->where('id', $data->id)
				            ->update(['status' =>'moved']);					    	
			    }
			}
		});


	}


	// 零件 =》 图片详情
	public static function pic()
	{
		// 下载所有的part页面
		Capsule::table('url_part')->where('status','wait')->orderBy('id')->chunk(20,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'url_part', 0777, true);
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 页面文件名
		    	$file = PROJECT_APP_DOWN.'url_part/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if(!file_exists($file))
		    	{
		    		$client = new Client();
		    		// 注册异步请求
					$client->getAsync(html_entity_decode($data->url),['verify' => false])->then(
					    function (ResponseInterface $res) use ($file, $data)
					    {
							if($res->getStatusCode()== 200)
				    		{
				    			// 保存文件
					            file_put_contents($file,$res->getBody());
					            // 命令行执行时候不需要经过apache直接输出在窗口
					            echo 'url_part '.$data->id.'.html'." download successful!\r\n";
				    		}
				    		if(file_exists($file))
					    	{
					            // 更改SQL语句
					            Capsule::table('url_part')
							            ->where('id', $data->id)
							            ->update(['status' =>'completed']);
					    	}
					    },
					    function (RequestException $e) {
					        echo $e->getMessage() . "\r\n";
					        echo $e->getRequest()->getMethod(). "\r\n";
					    }
					)->wait();
		    	}
		    }
		});
		// 获取所有的图片连接（最后一级别啦）
		Capsule::table('url_part')->where('status','completed')->orderBy('id')->chunk(20,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'url_part/'.$data->id.'.html';
		    	// 判定是否已经存在且合法
		    	if (file_exists($file))
		    	{
		    		$temp = file_get_contents($file);
		    		if($dom = HtmlDomParser::str_get_html($temp))
					{
						// 获取pic详情页url
						foreach($dom->find('.caption a') as $a)
						{
						    // 存储进去所有的part
						    $temp = [
						    	'url' => $prefix.$a->href,
						    	'status' => 'wait',
						    	'md5_url' => md5($prefix.$a->href)
						    ];
						    $empty = Capsule::table('url_pic')
						    	->where('md5_url', md5($prefix.$a->href))
						    	->get()
						    	->isEmpty();
						    if($empty)
						    {
							    Capsule::table('url_pic')->insert($temp);					    	
						    }
						}
			            // 更改SQL语句
			            Capsule::table('url_part')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'url_part '.$data->id.'.html'."  analyse successful!\r\n";
					}
		    	}
		    }
		});
	}
}