<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;


use Illuminate\Database\Schema\Blueprint;

/**
  * 下载所有零件详情页面
  * @author xu
  * @copyright 2018/01/29
  */
class fourstep{

	// 车 =》 零件
	public static function part()
	{
		// 下载所有的model页面
		Capsule::table('carinfo')->where('status','wait')->orderBy('id')->chunk(5,function($datas){
			// 创建文件夹
			@mkdir(PROJECT_APP_DOWN.'carinfo', 0777, true);
			// 并发请求
		    $guzzle = new guzzle();
		    $guzzle->poolRequest('carinfo',$datas);
		});

		// 获取所有的车连接
		Capsule::table('carinfo')->where('status','completed')->orderBy('id')->chunk(5,function($datas){
			$prefix ='https://partsouq.com';
			// 循环块级结果
		    foreach ($datas as $data)
		    {
		    	// 解析页面
		    	$file = PROJECT_APP_DOWN.'carinfo/'.$data->id.'.html';
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
						    	'md5_url' => md5($prefix.$a->href),
						    	'car_id' => $data->id
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
			            Capsule::table('carinfo')
					            ->where('id', $data->id)
					            ->update(['status' =>'readed']);
					    // 命令行执行时候不需要经过apache直接输出在窗口
			            echo 'carinfo '.$data->id.'.html'."  analyse successful!".PHP_EOL;
					}
		    	}
		    }
		});


		// 将所有part默认页面移动过去
		Capsule::table('carinfo')->where('status','readed')->orderBy('id')->chunk(5,function($datas){
			foreach ($datas as $data)
			{
				$temp = [
			    	'url' => $data->url,
			    	'status' => 'wait',
			    	'md5_url' => md5($data->url),
			    	'car_id' => $data->id
			    ];
			    $empty = Capsule::table('url_part')
			    	->where('md5_url',md5($data->url))
			    	->get()
			    	->isEmpty();
			    if($empty)
			    {
				    Capsule::table('url_part')->insert($temp);
			    	echo "carinfo ".$data->id." moved!".PHP_EOL;
			    	// 更改SQL语句
		            Capsule::table('carinfo')
				            ->where('id', $data->id)
				            ->update(['status' =>'moved']);					    	
			    }
			}
		});
	}

}