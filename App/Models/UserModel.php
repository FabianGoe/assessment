<?php

namespace FabianGO\Assessment\Models;

class UserModel
{
    /** @var array */
    private $settings;

    /** @var array */
    private $errors;

    public $db_id,
        $db_firstname,
        $db_initials,
        $db_lastname,
        $db_postal_code,
        $db_housenumber,
        $db_email,
        $db_phonenumber,
        $db_password;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param array $params
     * @param boolean $validateUserInput
     */
    public function initialize(array $params, $validateUserInput)
    {
        $this->errors = [];

        if (isset($params['id'])) {
            try {
                $this->setId((int)$params['id']);
            } catch (\InvalidArgumentException $e) {
                $this->errors[] = $e->getMessage();
            }
        }

        try {
            $this->setFirstName($params['firstname']);
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        }

        try {
            $this->setInitials($params['initials']);
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        }

        try {
            $this->setLastName($params['lastname']);
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        }

        try {
            $this->setPostalCode($params['postal_code']);
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        }

        try {
            $this->setHouseNumber($params['housenumber']);
        } catch (\InvalidArgumentException $e) {
            $this->errors[] = $e->getMessage();
        }

        if ($validateUserInput && !$this->validatePostalCode()) {
            $this->errors[] = 'Postal code and housenumber are invalid';
        }

        if (isset($params['email'])) {
            try {
                $this->setEmail($params['email']);
            } catch (\InvalidArgumentException $e) {
                $this->errors[] = $e->getMessage();
            }
        }

        if (isset($params['phonenumber'])) {
            try {
                $this->setPhoneNumber($params['phonenumber'], $validateUserInput);
            } catch (\InvalidArgumentException $e) {
                $this->errors[] = $e->getMessage();
            }
        }

        if ($validateUserInput) {
            $this->createPassword($params['password1'], $params['password2']);
        }
    }

    public function setId($id)
    {
        // not required
        if (empty($id)) {
            return;
        }

        $id = filter_var($id, FILTER_VALIDATE_INT);

        if ($id === false) {
            throw new \InvalidArgumentException('ID must be integer');
        }

        $this->db_id = $id;
    }

    public function setFirstName($firstname)
    {
        $firstname = filter_var($firstname, FILTER_SANITIZE_STRING);

        if (empty($firstname)) {
            throw new \InvalidArgumentException('First name is empty');
        }

        $this->db_firstname = $firstname;
    }

    public function setInitials($initials)
    {
        $initials = filter_var($initials, FILTER_SANITIZE_STRING);

        if (empty($initials)) {
            throw new \InvalidArgumentException('Initials are empty');
        }

        if ($this->db_firstname === null || empty($this->db_firstname)) {
            throw new \InvalidArgumentException('Cannot set initials without firstname');
        }

        if (strtolower(substr($initials, 0, 1)) !== strtolower(substr($this->db_firstname, 0, 1))) {
            throw new \InvalidArgumentException('Initials do not match firstname');
        }

        $this->db_initials = $initials;
    }

    public function setLastName($lastname)
    {
        $lastname = filter_var($lastname, FILTER_SANITIZE_STRING);

        if (empty($lastname)) {
            throw new \InvalidArgumentException('Last name is empty');
        }

        $this->db_lastname = $lastname;
    }

    public function setPostalCode($postalCode)
    {
        $postalCode = filter_var($postalCode, FILTER_SANITIZE_STRING);

        if (empty($postalCode)) {
            throw new \InvalidArgumentException('Postal code is empty');
        }

        if (preg_match('/^[1-9][0-9]{3} ?(?!sa|sd|ss)[a-z]{2}$/i', $postalCode) == false) {
            throw new \InvalidArgumentException('Postal code is invalid');
        }

        $this->db_postal_code = str_replace(' ', '', $postalCode);
    }

    public function setHouseNumber($houseNumber)
    {
        if (!$houseNumber) {
            throw new \InvalidArgumentException('Housenumber is required');
        }

        $houseNumber = filter_var($houseNumber, FILTER_VALIDATE_INT);

        if (!$houseNumber) {
            throw new \InvalidArgumentException('Housenumber is invalid');
        }

        $this->db_housenumber = $houseNumber;
    }

    /**
     * Use API to check if postal code is correct
     *
     * @return bool
     */
    public function validatePostalCode()
    {
        // do not bother if fields are not filled
        if (empty($this->db_postal_code) || empty($this->db_housenumber)) {
            return false;
        }

        try {
            $params = [
                'headers' => [
                    'X-Api-Key' => $this->settings['postcode-api-key'],
                ],
                'query' => [
                    'postcode' => $this->db_postal_code,
                    'number' => $this->db_housenumber
                ]
            ];

            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', 'https://postcode-api.apiwise.nl/v2/addresses/', $params);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['_embedded']) || !isset($data['_embedded']['addresses']) || count($data['_embedded']['addresses']) == 0) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->errors[] = 'Could not validate postal code';

            return false;
        }
    }

    /**
     * @param $email
     *
     * @throws \InvalidArgumentException
     */
    public function setEmail($email)
    {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $email = filter_var($email, FILTER_VALIDATE_EMAIL);

        if ($email === false) {
            throw new \InvalidArgumentException('Email is invalid');
        }

        $this->db_email = $email;
    }

    /**
     * @param $number
     * @param $convertUserInput
     *
     * @throws \InvalidArgumentException
     */
    public function setPhoneNumber($number, $convertUserInput)
    {
        // not required
        if (empty($number)) {
            return;
        }

        try {
            $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $phoneNumberObject = $phoneNumberUtil->parse($number, 'NL');
        } catch (\libphonenumber\NumberParseException $e) {
            throw new \InvalidArgumentException('Phone number is not valid');
        }

        if ($phoneNumberUtil->isPossibleNumber($phoneNumberObject) === false) {
            throw new \InvalidArgumentException('Phone number is not valid');
        }

        if ($convertUserInput) {
            $number = $phoneNumberUtil->format($phoneNumberObject, \libphonenumber\PhoneNumberFormat::INTERNATIONAL);
        }

        $this->db_phonenumber = $number;
    }

    public function createPassword($password1, $password2)
    {
        if ($this->validatePasswords($password1, $password2)) {
            $this->db_password = password_hash($password1, PASSWORD_DEFAULT, ['cost' => 14]);
        }
    }

    public function validatePasswords($password1, $password2)
    {
        $correct = true;

        if ($password1 !== $password2) {
            $this->errors[] = 'Passwords do not match';
            $correct = false;
        }

        if (strlen($password1) < 8) {
            $this->errors[] = 'Passwords need to be at least 8 characters long';
            $correct = false;
        }

        if (!preg_match("/[0-9]+/", $password1)) {
            $this->errors[] = "Password must include at least one number";
            $correct = false;
        }

        if (!preg_match("/[A-Z]+/", $password1)) {
            $this->errors[] = "Password must include at least one upper-case letter";
            $correct = false;
        }

        if (!preg_match("/[a-z]+/", $password1)) {
            $this->errors[] = "Password must include at least one lower-case letter";
            $correct = false;
        }

        return $correct;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}