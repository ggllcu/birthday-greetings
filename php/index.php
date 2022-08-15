<?php

class ContactList
{
    // Afaik, it's not possible to declare a type as Array of Type
    protected array $contacts;

    public function __construct()
    {
        $this->contacts = [];
    }

    public function getBirthdayContacts(DateTime $date = new DateTime()): array
    {
        return array_filter($this->contacts, function (Contact $contact) use ($date) {
            return $contact->isDateBirthday($date);
        });
    }

    public function getNonBirthdayContacts(DateTime $date = new DateTime()): array
    {
        return array_filter($this->contacts, function (Contact $contact) use ($date) {
            return !$contact->isDateBirthday($date);
        });
    }

    public function addContacts($source): void
    {
        // TODO : should check that reader extends DataReader
        $csvReader = new CsvReader();
        $contactsData = $csvReader->read($source);

        foreach ($contactsData as $contactData) {
            $this->addContact(new Contact($contactData));
        }
    }

    public function addContact(Contact $contact): void
    {
        $this->contacts[] = $contact;
    }
}

class Contact
{
    public string $first_name;
    public string $last_name;
    public string $full_name;
    public string $email;
    public DateTime $date_of_birth;

    public function __construct(array $contactData)
    {
        $this->first_name = trim($contactData['first_name']);
        $this->last_name = trim($contactData['last_name']);
        $this->email = trim($contactData['email']);
        $this->date_of_birth = $contactData['date_of_birth'];
        $this->full_name = $this->first_name . " " . $this->last_name;
    }

    public function isDateBirthday(DateTime $date = new DateTime()): bool
    {
        if ($this->is2802($date)) {
            if ($this->is2902($this->date_of_birth)) {
                return true;
            }
        }
        if ($this->is2902($date)) {
            return false;
        }
        return $this->haveSameMonth($this->date_of_birth, $date) && $this->haveSameDay($this->date_of_birth, $date);
    }

    public function getBirthdayMessage(): string
    {
        return "Happy birthday, dear $this->first_name!";
    }

    public function getNonBirthdayMessage($birthdayContacts): string
    {
        if (count($birthdayContacts) === 1) {
            $birthdayContact = $birthdayContacts[0];
            $contactsMessage = $birthdayContact->first_name . " " . $birthdayContact->last_name;
        } else {
            $lastContact = array_pop($birthdayContacts);
            $fullnames = array_map(function ($contact) {
                return $contact->full_name;
            }, $birthdayContacts);

            $contactsMessage = implode(", ", $fullnames);
            $contactsMessage .= " and " . $lastContact->first_name . " " . $lastContact->last_name;
        }

        return <<<EOD
Dear $this->first_name,

Today is $contactsMessage's birthday.
Don't forget to send them a message !
EOD;
    }

    private function is2802(DateTime $date): bool
    {
        return $date->format('d') === '28' && $date->format('m') === '02';
    }

    private function is2902(DateTime $date): bool
    {
        return $date->format('m') === '02' && $date->format('d') === '29';
    }

    private function haveSameMonth(DateTime $date1, DateTime $date2): bool
    {
        return $date1->format('m') === $date2->format('m');
    }

    private function haveSameDay(DateTime $date1, DateTime $date2): bool
    {
        return $date1->format('d') === $date2->format('d');
    }
}

interface Sender
{
    public function send(Contact $contact, string $message): void;
}

class EmailSender implements Sender
{
    public function send(Contact $contact, string $message): void
    {
        echo "Email sent to contact: " . $contact->email . PHP_EOL;
        echo $message . PHP_EOL;
    }
}

interface DataReader
{
    public function read($source): array;
    public function formatData($data): array;
}

class CsvReader implements DataReader
{

    public function formatData($data): array
    {
        return [
            'last_name' => $data[0],
            'first_name' => $data[1],
            'date_of_birth' => new DateTime($data[2]),
            'email' => $data[3],
        ];
    }

    public function read($source): array
    {
        $row = 1;
        $contacts = [];

        if (($handle = fopen($source, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== FALSE) {
                if ($row === 1) {
                    $row++;
                    continue;
                }
                $contacts[] = $this->formatData($data);
                $row++;
            }
            fclose($handle);
        }

        return $contacts;
    }
}

class ContactListController
{
    public function sendBirthdayEmails(ContactList $list, DateTime $date): void
    {
        echo "Sending birthday emails..." . PHP_EOL;
        $birthdayContacts = $list->getBirthdayContacts($date);
        if (count($birthdayContacts) === 0) {
            echo "Done!" . PHP_EOL . PHP_EOL;
            return;
        }

        $sender = new EmailSender();

        foreach ($birthdayContacts as $contact) {
            $sender->send($contact, $contact->getBirthdayMessage());
            echo "---" . PHP_EOL;
        }
        echo "Done!" . PHP_EOL . PHP_EOL;
    }

    public function sendNonBirthdayEmails(ContactList $list, DateTime $date): void
    {
        echo "Sending non birthday emails..." . PHP_EOL;
        $nonBirthdayContacts = $list->getNonBirthdayContacts($date);
        if (count($nonBirthdayContacts) === 0) {
            echo "Done!" . PHP_EOL . PHP_EOL;
            return;
        }

        $birthdayContacts = $list->getBirthdayContacts($date);
        if (count($birthdayContacts) === 0) {
            echo "Done!" . PHP_EOL;
            return;
        }

        $sender = new EmailSender();

        foreach ($nonBirthdayContacts as $contact) {
            $sender->send($contact, $contact->getNonBirthdayMessage($birthdayContacts));
            echo "---" . PHP_EOL;
        }
        echo "Done!" . PHP_EOL;
    }
}

function main(): void
{
    // Optional, usefull for test
    $dateString = readline("Enter the date in YYYY/MM/DD format:" . PHP_EOL);

    $checkRegex = '/^\d{4}\/\d{2}\/\d{2}$/';
    if (preg_match($checkRegex, $dateString) === 0) {
        echo "Wrong format.";
        die();
    }
    $date = new DateTime($dateString);

    $list = new ContactList();
    $list->addContacts('./data.csv');

    $controller = new ContactListController();
    $controller->sendBirthdayEmails($list, $date);
    $controller->sendNonBirthdayEmails($list, $date);
}

main();
