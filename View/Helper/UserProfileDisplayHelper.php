<?php
/**
 * [Helper] UserProfileDisplay
 *
 * @link			http://www.materializing.net/
 * @author			arata
 * @license			MIT
 */
class UserProfileDisplayHelper extends AppHelper {
/**
 * ヘルパー
 *
 * @var array
 */
	public $helpers = array( 'BcBaser', 'BcHtml', 'BcUpload');
	
/**
 * 有効になっているか判定する
 * 
 * @param array $data
 * @return boolean
 */	
	public function judgeStatus($data = array()) {
		if ($data) {
			if (isset($data['UserProfileDisplay'])) {
				if ($data['UserProfileDisplay']['status']) {
					return true;
				}
			} else {
				$key = key($data);
				if ($data[$key]['status']) {
					return true;
				}
			}
		}
		return false;
	}
	
/**
 * 有効状態を判定する
 * 
 * @param array $data
 * @param string $modelName
 * @return boolean 有効状態
 */
	public function allowPublish($data, $modelName = '') {
		if ($modelName) {
			$data = $data[$modelName];
		}
		$allowPublish = (int)$data['status'];
		return $allowPublish;
	}
	
/**
 * 表示名を取得する
 * 
 * @param array $post
 * @param array $options
 * @return string
 */
	public function getShowName($post = array(), $options = array()) {
		$_options = array(
			'delimiter' => ' ',
		);
		$options = Hash::merge($_options, $options);
		
		$name = '';
		// キーと値の参照: Configure::read('UserProfileDisplay.show_name')
		switch ($post['UserProfileDisplay']['show_name']) {
			case '1':
				$name = $post['UserProfileDisplay']['name'];
				break;
			
			case '2':
				$name = $post['User']['real_name_1'] . $options['delimiter'] . $post['User']['real_name_2'];
				break;
			
			case '3':
				$name = $post['User']['real_name_1'];
				break;
			
			case '4':
				$name = $post['User']['real_name_2'];
				break;
			
			case '5':
				$name = $post['User']['real_name_2'] . $options['delimiter'] . $post['User']['real_name_1'];
				break;
			
			case '6':
				$name = $post['User']['name'];
				break;
			
			default:
				$name = $post['User']['nickname'];
				break;
		}
		return $name;
	}
	
	public function getBlogPost() {
		
	}
	
/**
 * プロフィール表示画像を取得する
 * 
 * @param array $post
 * @param array $options
 * @return string
 */
	public function getShowImage($post = array(), $options = array()) {
		$_options = array(
			'width' => '100',
			'alt' => '',
			'link' => false,
			'noimage' => '',
			'imgsize' => 'small',
		);
		$options = Hash::merge($_options, $options);
		
		$image = '';
		// キーと値の参照: Configure::read('UserProfileDisplay.show_image')
		switch ($post['UserProfileDisplay']['show_image']) {
			case '1':
				// 画像指定
				$image = $this->BcUpload->uploadImage('UserProfileDisplay.file', $post['UserProfileDisplay']['file'], $options);
				break;
			
			case '2':
				// Gravatar
				$image = $this->get_gravatar($post['UserProfileDisplay']['gravatar_email'],
					array(
						'size' => $post['UserProfileDisplay']['gravatar_size'],
						'rating' => $post['UserProfileDisplay']['gravatar_rating'],
						'img' => true
					)
				);
				break;
			
			default:
				// 表示なし
				break;
		}
		return $image;
	}
	
/**
 * Get either a Gravatar URL or complete image tag for a specified email address.
 *
 * @param string $email The email address
 * @param string $s Size in pixels, defaults to 80px [ 1 - 2048 ]
 * @param string $d Default imageset to use [ 404 | mm | identicon | monsterid | wavatar ]
 * @param string $r Maximum rating (inclusive) [ g | pg | r | x ]
 * @param boole $img True to return a complete IMG tag False for just the URL
 * @param array $attr Optional, additional key/value attributes to include in the IMG tag
 * @return String containing either just a URL or a complete image tag
 * @source http://gravatar.com/site/implement/images/php/
 */
	function get_gravatar( $email, $options = array(), $attr = array() ) {
		$_options = array(
			'size' => 80,
			'avatar' => 'mm',
			'rating' => 'g',
			'img' => false,
		);
		$options = Hash::merge($_options, $options);
		
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5( strtolower( trim( $email ) ) );
		//$url .= "?s=$s&d=$d&r=$r";
		$url .= '?s='. $options['size'] .'&d='. $options['avatar'] .'&r='. $options['rating'];
		
		$result = $this->curlRequest($url);
		$content = $result['content'];
		if ($result['info']['http_code']) {
			if ( $options['img'] ) {
				$content = $this->BcBaser->getImg($url, $attr);
				//$url = '<img src="' . $url . '"';
				//foreach ( $attr as $key => $val )
				//	$url .= ' ' . $key . '="' . $val . '"';
				//$url .= ' />';
			}
		} else {
			$content = 'Gravatarからの画像取得に失敗しました。ネットワークに繋がっていないか、サービスが停止している可能性があります。';
		}
		return $content;
	}
	
/**
 * HTTPリクエストを実行して情報を取得する
 * 
 * @param string $url HTTPリクエストを実行する対象URL
 * @param string $header 送信するHTTPヘッダ
 * @param string $option オプション
 * @link http://php.net/manual/ja/curl.examples-basic.php
 * @link http://php.net/manual/ja/function.curl-setopt.php cURL:curl_setopt 転送用オプション
 * @link http://so-zou.jp/web-app/tech/programming/php/network/curl/#no1
 * @link http://qiita.com/Shadow/items/a5f9574fadd214d7b5c8
 */
	function curlRequest($url, $options = array(), $header = array()) {
		$_header = array();
		$header = Hash::merge($_header, $header);
		
		$_options = array(
			'timeout' => 2,
			'return_transfer' => true,
		);
		$options = Hash::merge($_options, $options);
		
		// 初期化処理
		$ch = curl_init();
		// 取得するURLを設定
		curl_setopt($ch, CURLOPT_URL, $url);
		
		// 成功した場合に TRUE、失敗した場合に FALSE を返す。
		// オプション CURLOPT_RETURNTRANSFER が設定されていると、 成功した場合に取得結果、失敗した場合に FALSE を返す
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, $options['return_transfer']);
		
		if ($header) {
			// 設定する HTTP ヘッダフィールドの配列
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		}
		
		// cURL 関数の実行にかけられる時間の最大値
		curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
		
		// 転送実行
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		// 転送セッションの終了
		curl_close($ch);
		
		return array('info' => $info, 'content' => $output);
	}
	
}