# MFA - Multi-Factor Authentication

* Authenticator app codes - TOTP (Time-based One-Time Password)
* Printed backup codes - for when you lose your phone
* SMS codes - not very secure, but easy for many users
* Email codes - since email is often used for password recovery, this is not a good choice.

Each user can have multiple MFA methods - e.g. SMS and Authenticator app and backup codes.

SNS (email) codes are generated with TOTP with the secret generated without user interaction and stored in the database.

The backup codes are generated with HOTP in a bucket of 10. The counter is stored in the database. Checkup of the code is
done whith the counter+1, 2, 3, 4, 5, 6, 7, 8, 9, 10. If the code is used it is marked as used in the database. When all
codes are used, or when the user requests new codes, a new bucket is generated, invalidating the old codes updating the counter + 10.
