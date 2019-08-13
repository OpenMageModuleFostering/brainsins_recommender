<?php
class Brainsins_Recommender_Model_Recommendation extends Mage_Core_Model_Abstract {

	private static $_patternRecId = '/idRecommendation=(.*)&/';
	private static $_patternPrevPage = '/idPrevPage=(\d*)/';

    private $recommended = Array();

	protected function _construct() {
		parent::_construct();
		$this->_init('brainsins_recommender/recommendation');

		$key = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());


		if ($this->dev) {
			$this->server = "dev-api.brainsins.com";
		} else {
			$this->server = "api.brainsins.com";
		}
	}

	public function getIdRecommendation($id) {

        if (!array_key_exists($id, $this->recommended)) {
            return "";
        }

        $url = $this->recommended[$id];

		$matches = null;
		preg_match(self::$_patternRecId, $url, $matches, PREG_OFFSET_CAPTURE);
		if (count($matches) > 1) {
			$match = $matches[1];
			if (count($match > 0)) {
				return $match[0];
			}
		}

		return "";
	}

	public function getPrevPage($id) {

        if (!array_key_exists($id, $this->recommended)) {
            return "";
        }

        $url = $this->recommended[$id];

		$matches = null;
		preg_match(self::$_patternPrevPage, $url, $matches, PREG_OFFSET_CAPTURE);
		if (count($matches) > 1) {
			$match = $matches[1];
			if (count($match > 0)) {
				return $match[0];
			}
		}

		return "";
	}

	public function getRequestUrl($recommenderId, $productId, $userId, $lang, $divName, $filterCategories, $filterLevel, $detailsLevel, $maxResults) {
		
		$enabled = Mage::getStoreConfigFlag('brainsins_recommender_options/brainsins_recommender_general/enabled', Mage::app()->getStore()->getStoreId());

		if (!$enabled) {
			return "";
		}
		
		$_apiMode = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_advanced/api_mode', Mage::app()->getStore()->getStoreId());

		$key = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());
		if (!isset($key) || !$key || $key == '') {
			return "";
		}

		$url = "http://recommender.brainsins.com/recommender.php";

		$url .= "?token=" . $key;
		$url .= "&userId=" . $userId;

		$url .= "&recId=" . $recommenderId;

		if (isset($productId) && $productId != "") {
			$url .= "&prodId=" . $productId;
		}

		if (isset($lang) && $lang != "") {
			$url .= "&lang=" . $lang;
		}

		if ($divName) $url .= "&dN=" . $divName;
		if (isset($filterLevel) && $filterLevel != "" ) {
			$url .= "&filter=" . $filterLevel;
		} else {
			//default is all
			$url .= "&filter=all";
		}

		if (isset($filterCategories) && $filterCategories != "") {
			$url .= "&cat=" . $filterCategories;
		}
		
		if (isset($detailsLevel) && $detailsLevel != "") {
			$url .= "&details=$detailsLevel";
		}

		if (isset($maxResults) && $maxResults != "") {
			$url .= "&nr=" . $maxResults;
		}

		return $url;
	}

	public function requestRecommendations($recommenderId, $productId, $userId, $lang, $divName, $filterCategories, $filterLevel, $detailsLevel, $maxResults) {

		$key = Mage::getStoreConfig('brainsins_recommender_options/brainsins_recommender_general/bs_key', Mage::app()->getStore()->getStoreId());

		$url = $this->getRequestUrl($recommenderId, $productId, $userId, $lang, $divName, $filterCategories, $filterLevel, $detailsLevel, $maxResults);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		$timeout = 2;
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: text/plain", "Accept: text/plain"));
		$result = curl_exec($curl);
		curl_close($curl);

		$productList = array();

		$json = json_decode($result, true);

		$data = $json["data"];
		$count = $data['count'];

		if ($count == "0") {
			return;
		}

		$list = array();

		if (is_array($data) && array_key_exists('list', $data)) {
			$list = $data['list'];
		} else if (is_array($json) && array_key_exists('list', $json)) {
			$list = $json['list'];
		}

		if ($count == "1" && array_key_exists("id", $list)) {
			$aux = $list;
			$list = array();
			$list[] = $aux;
		}

		$recommendations = array();
		$idRecommendation = null;
		$idPrevPage = null;

		foreach($list as $item) {
			$id = $item["id"];
			$url = $item["url"];
			$recommendations[] = $id;
            $this->recommended[$id] = $url;
		}

        $this->__set("filtered", false);

        if ($count > 0) {
            $algorithm = $list[0]["algorithm"];
            $this->__set("altorithm", $algorithm);

            if ($filterCategories != null && $filterCategories != "") {
                $categories = $list[0]["categories"];
                if (strpos($categories, $filterCategories) !== false) {
                    $this->__set("filtered", true);
                }
            }
        }

		$this->__set("recommendations", $recommendations);
		$this->__set("idRecommendation", $idRecommendation);
		$this->__set("idPrevPage", $idPrevPage);

	}
}