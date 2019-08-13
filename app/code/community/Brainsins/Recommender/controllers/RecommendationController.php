<?php

class Brainsins_Recommender_RecommendationController extends Mage_Core_Controller_Front_Action {

	public function recommendationsAction() {
		
		
		
		$template = $this->getRequest()->getParam("template");
		$recommenderId = $this->getRequest()->getParam("recommenderId");
		$userId = $this->getRequest()->getParam("userId");
		$productId = $this->getRequest()->getParam("productId");
		$lang= $this->getRequest()->getParam("lang");
		$divName = $this->getRequest()->getParam("divName");
		$filterCategories = $this->getRequest()->getParam("filterCategories");
		$filterLevel = $this->getRequest()->getParam("filterLevel");
		$detailsLevel = $this->getRequest()->getParam("detailsLevel");
		$numRecs = $this->getRequest()->getParam("numRecs");
		$maxResults = $this->getRequest()->getParam("maxResults");
		$debug = $this->getRequest()->getParam("debug") == "1";
		
		$model = Mage::getSingleton("brainsins_recommender/recommendation");
		if ($debug) {
			$url = $model->getRequestUrl($recommenderId, $productId, $userId, $lang, $divName, $filterCategories, $filterLevel, $detailsLevel, $maxResults);
			$html = "<a href='$url'>$url</a>";
			$this->getResponse()->setBody($html);
			return;
		}

		$model->requestRecommendations($recommenderId, $productId, $userId, $lang, $divName, $filterCategories, $filterLevel, $detailsLevel, $maxResults);
		$recommendationsBlock = $this->getLayout()
		->createBlock('brainsins_recommender/recommendations');
		$recommendationsBlock->setTemplate($template);
		$this->getResponse()->setBody($recommendationsBlock->toHtml());
	}
}