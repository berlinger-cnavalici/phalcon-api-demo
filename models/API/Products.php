<?php

namespace API;

use Phalcon\Mvc\Model;
use Phalcon\Validation;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\PresenceOf;

class Products extends Model
{
    protected $id;

    protected $name;

    protected $price;

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

    public function validation()
    {
        $validator = new Validation();

        $validator->add("name",
            new StringLength([
                "max"            => 10,
                "min"            => 2,
                "messageMaximum" => "Name should be maximum 10 characters",
                "messageMinimum" => "Name should be minimum 2 characters"
            ])
        );

        $validator->add("name",
            new PresenceOf(["message" => "The name is required"])
        );

        return $this->validate($validator);
    }

    public function getErrorsAsArray()
    {
        foreach ($this->getMessages() as $message) {
            $errors[$message->getField()] = $message->getMessage();
        }

        return $errors;
    }

    public function beforeSave()
    {
        // Convert the array into a string
        // $this->status = join(",", $this->status);
    }

    public function afterFetch()
    {
        // Convert the string to an array
        // $this->status = explode(",", $this->status);
    }

    public function afterSave()
    {
        // Convert the string to an array
        // $this->status = explode(",", $this->status);
    }
}