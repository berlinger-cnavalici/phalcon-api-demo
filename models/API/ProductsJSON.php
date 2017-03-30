<?php

namespace API;

use PhalconRest\API\BaseModel;

class ProductsJSON extends BaseModel
{
    protected $id;

    protected $name;

    protected $price;

    public function initialize()
    {
        parent::initialize();
        $this->blockColumns = [];

        $this->setSource('products');
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getPrice()
    {
        return (double)$this->price;
    }
}