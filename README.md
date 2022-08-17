# birthday-greetings
Solving https://codingdojo.org/kata/birthday-greetings/

## Usage

```
git clone git@github.com:ggllcu/birthday-greetings.git
```

```
cd birthday-greetings
```

### PHP

```
cd php && docker  run  -v $PWD:$PWD -w $PWD -i -t  php:cli-alpine php index.php
```

## Test cases

- YYYY/02/28 -> Two birthdays, one 28/02 and one 29/02 (the year must be a leap year), three non birthday emails
- YYYY/02/29 -> No birthdays, already sent on 28/02; no email
- YYYY/08/15 -> Three birthdays, two non birthday emails
- Other days -> No birthdays, no email

