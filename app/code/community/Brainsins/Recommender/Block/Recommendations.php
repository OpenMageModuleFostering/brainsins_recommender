<?php
class Brainsins_Recommender_Block_Recommendations extends Mage_Catalog_Block_Product_Abstract {	
	
	public function getRecommendations() {
		$model = Mage::getSingleton("brainsins_recommender/recommendation");
		return $model->__get("recommendations");
	}
	
	public function getIdRecommendation($url) {
        $model = Mage::getSingleton("brainsins_recommender/recommendation");
        return $model->getIdRecommendation($url);
	}
	
	public function getIdPrevPage($url) {
		$model = Mage::getSingleton("brainsins_recommender/recommendation");
        return $model->getPrevPage($url);
		//return $model->__get("idPrevPage");
	}
	
	public function getOnclick($id, $url) {	
		return "bsTrackProductClicked('$id','" . $this->getIdRecommendation($id) . "','" . $this->getIdPrevPage($id) . "','$url');return false;";
	}

    public function getFirstAlborithmCode() {
        $model = Mage::getSingleton("brainsins_recommender/recommendation");
        return " <-" . $model->__get("altorithm");
    }

    public function isFiltered() {
        $model = Mage::getSingleton("brainsins_recommender/recommendation");
        return $model->__get("filtered") === true;
    }
}