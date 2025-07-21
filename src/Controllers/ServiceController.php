<?php
namespace Src\Controllers;
use Src\Models\Service;

class ServiceController {
    protected Service $serviceModel;

    public function __construct() {
        $this->serviceModel = new Service();
    }

    public function getBySlug($slug) {
        return $this->serviceModel->getBySlug($slug);
    }

    public function getServiceWithInclusionsAndExtras($slug) {
        $service = $this->serviceModel->getBySlug($slug);
        $inclusions = $this->serviceModel->getInclusions($service['id']);
        $extras = $this->serviceModel->getExtras($service['id']);

        return [
            'service' => $service,
            'inclusions' => $inclusions,
            'extras' => $extras,
        ];
    }
}
