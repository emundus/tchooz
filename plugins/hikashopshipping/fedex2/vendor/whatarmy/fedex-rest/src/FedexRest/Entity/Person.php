<?php


namespace FedexRest\Entity;


class Person
{
    public ?Address $address = null;
    public string $personName = '';
    public string $phoneNumber;
    public string $companyName = '';

    public function withAddress(Address $address)
    {
        $this->address = $address;
        return $this;
    }


    public function setPersonName(string $personName)
    {
        $this->personName = $personName;
        return $this;
    }

    public function setPhoneNumber(string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function setCompanyName(string $companyName)
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function prepare(): array
    {
        $data = [];
        if (!empty($this->personName)) {
            $data['contact']['personName'] = $this->personName;
        }
        if (!empty($this->phoneNumber)) {
            $data['contact']['phoneNumber'] = $this->phoneNumber;
        }
        if (!empty($this->companyName)) {
            $data['contact']['companyName'] = $this->companyName;
        }

        if ($this->address != null) {
            $data['address'] = $this->address->prepare();
        }
        return $data;
    }
}
