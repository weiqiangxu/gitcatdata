<?php
// 引入数据库层
use Illuminate\Database\Capsule\Manager as Capsule;
// 解析HTML为DOM工具
use Sunra\PhpSimple\HtmlDomParser;
use Illuminate\Database\Schema\Blueprint;

/**
  * @author xu
  * @copyright 2018/01/29
  */
class threestep{

	// 车系=》车
	public static function part_detail()
	{
		$guzzle = new guzzle();
		// 下载
		$empty = Capsule::table('car_part')->where('status','wait')->get()->isEmpty();
		@mkdir(PROJECT_APP_DOWN.'car_part', 0777, true);
		while(!$empty) {
			$datas = Capsule::table('car_part')->where('status','wait')->limit(100)->get();
		    $guzzle->poolRequest('car_part',$datas);
		    $empty = Capsule::table('car_part')->where('status','wait')->get()->isEmpty();
		}

		// 解析
		$empty = Capsule::table('car_part')->where('status','completed')->get()->isEmpty();
		$url_prefix = 'http://www.toyodiy.com/parts/';
		while(!$empty) {
			$datas = Capsule::table('car_part')->where('status','completed')->limit(5)->get();
			foreach ($datas as $data) {

				$file = PROJECT_APP_DOWN.'car_part/'.$data->id.'.html';

				if(!file_exists($file))
				{
					echo PROJECT_APP_DOWN.'car_part/'.$data->id.'.html not found!'.PHP_EOL;
					Capsule::table('car_part')->where('id', $data->id)->update(['status' =>'notfound']);
					continue;
				}
				if($dom = HtmlDomParser::str_get_html(file_get_contents($file)))
				{
					// 下一页
					if($dom->find('.phdr',0)){

						if($dom->find('.phdr',0)->last_child() && strpos($dom->find('.phdr',0)->last_child()->plaintext, 'ext'))
						{
							$temp = array(
								'url' => $url_prefix.$dom->find('.phdr',0)->last_child()->href,
								'car_id' => $data->car_id,
								'status' => 'wait',
								'part_type' => $data->part_type,
								'part_type_num' => $data->part_type_num,
								'part_type_page' => $data->part_type_page+1,
							);
							// 入库
							$empty = Capsule::table('car_part')->where('url',$url_prefix.$dom->find('.phdr',0)->last_child()->href)->get()->isEmpty();
							if($empty) Capsule::table('car_part')->insert($temp);
						}
					}
					// 获取前缀
					$prefix = '';
					if($dom->find(".phdr",0))
					{
						// 全部获取
						$prefix = str_replace('&emsp;','',$dom->find(".phdr",0)->plaintext);
						if($dom->find(".phdr",0)->find('a',0))
						{
							// 如果有一个a标签去除prev或者next
							$first_a = $dom->find(".phdr",0)->find('a',0)->plaintext;
							$prefix = str_replace($first_a,'', $prefix);
							// 如果有两个a标签去除
							if($dom->find(".phdr",0)->find('a',1))
							{
								$next_a = $dom->find(".phdr",0)->find('a',1)->plaintext;
								$prefix = str_replace($next_a,'', $prefix);
							}
						}
						$prefix = htmlspecialchars_decode($prefix);
					}


					// 号码
					if($dom->find('#t2',0))
					{
						$name = '';

						foreach ($dom->find('#t2',0)->find('tr')  as $line => $tr)
						{
							$tmp = array();
							// 细文本
							if(!$tr->getAttribute('class'))
							{
								$num =  $tr->find('td',0)->plaintext;
								if(!empty($num))
								{
									$des = $tr->find('td',1)->plaintext;
									$sum = $tr->find('td',2)->plaintext;
									$tmp = [
										'car_id' => $data->car_id,
										'url' => $data->url,
										'part_type' => $data->part_type,
										'part_type_num' => $data->part_type_num,
										'part_type_page' => $data->part_type_page,
										'part_detail_num' =>$num,
										'part_detail_name' => trim($name),
										'part_detail_des' => trim($des),
										'part_detail_prefix' => $prefix,
										'part_detail_sum' => $sum
									];
								}
							}
							else
							{
								// 名称
								if($tr->find('td',1)){
									$name = $tr->find('td',1)->plaintext;
								}


								// 下一行是粗的 或者 不存在 = 号码
								if(!$tr->next_sibling() || ($tr->next_sibling()->tag!='tr') || ($tr->next_sibling()->getAttribute('class')=='h'))
								{
									$num =  $tr->find('td',0)->plaintext;
									$sum = $tr->find('td',2)->plaintext;
									$tmp = [
										'car_id' => $data->car_id,
										'url' => $data->url,
										'part_type' => $data->part_type,
										'part_type_num' => $data->part_type_num,
										'part_type_page' => $data->part_type_page,
										'part_detail_num' =>$num,
										'part_detail_name' => trim($name),
										'part_detail_prefix' => $prefix,
										'part_detail_sum' => $sum
									];
								}
							}

							// 入库
							if(!empty($tmp)) Capsule::table('part_detail')->insert($tmp);
						}
					}
					else
					{
						echo 'car_part id '.$data->id.' data not found!'.PHP_EOL;
					}
				    // 更新状态
				    Capsule::table('car_part')->where('id', $data->id)->update(['status' =>'readed']);
					echo 'car_part '.$data->id.' analyse completed!'.PHP_EOL;
					// 清理内存防止内存泄漏
					$dom-> clear(); 
				}
			}
		    $empty = Capsule::table('car_part')->where('status','completed')->get()->isEmpty();
		}
	}
}