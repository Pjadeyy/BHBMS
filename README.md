## BHouse Billing Hub
Boarding House Rent Billing Management System, referred to as the BHouse Billing Hub It allows automated tracking of rent, reminders for payments, and generation of reports.

## Technology Stack

- **PHP**: Server-side scripting language to manage business logic and data processing.
- **MySQL**: Database to store user, boarders, payments, and more.
- **HTML, CSS, JavaScript**: Front-end technologies to ensure a smooth and interactive user experience.
- **AJAX**: For smooth asynchronous interactions between the client and server.
- **Apache**: Web server for hosting the application.

## Features
- **User Authentication**: Allows user to login/signup.
- **Boarders Profile**: records boarders information, add, edit, and delete, rooms, address, move in date, and more.
- **Receipt/Invoice**: Stores past/recent receipts and print to PDF.
- **Financial Reports**: Stores and Exports monthly, yearly and visitors reports collection to PDF.
- **Communication**: Sends email notifications to boarders before their due date.

## Installation
 1. Clone the repository

     ```
      git clone https://github.com/prixane/BHBMS.git
      cd BHBMS
      ```
 2. Install dependencies:

    ```
      composer install
      ```
 3. Create a .env file in the root directory with the following variables:

     ```
      DB_HOST=localhost
      DB_USER=your_database_user
      DB_PASS=your_database_password
      DB_NAME=bh
      
      GOOGLE_CLIENT_ID=your_google_client_id
      GOOGLE_CLIENT_SECRET=your_google_client_secret
      GOOGLE_REDIRECT=http://localhost/BHBMS/googleAuth/google-callback.php
      ```
  4. Import the database schema
      ```
      mysql -u your_database_user -p your_database_name < bh.sql
      ```
     
## Prerequisites
- PHP (>= 8.2.12)
- MySQL 
- Apache server (or any compatible server)
- Composer (for dependency management)

## Steps to Set Up Locally
- Open your XAMPP Control Panel and start Apache and MySQL.
- Extract the downloaded source code zip file.
- Copy the extracted source code folder and paste it into the XAMPP's "htdocs" directory.
- Browse the PHPMyAdmin in a browser. i.e. http://localhost/phpmyadmin
- Create a new database naming bh.
- Import the provided SQL file. 
- Browse the Boarding House Billing Management System in a browser. i.e.
  http://localhost/BHBMS/authentication/welcome.php
  
## Usage
- **For Admin**: To manage boarders, payment, visitor payment, receipt and more.

## Screenshots

- Welcome Page

  ![Welcome](https://github.com/user-attachments/assets/32541ec1-dc66-4040-bc94-6645d19d53fc)

- Login/Register
  
  ![login](https://github.com/user-attachments/assets/4f3c5f59-09c8-4531-8526-2abf51c06bdc)

  ![register](https://github.com/user-attachments/assets/1aded0f5-bc7d-4eb8-8277-da7ed63942f3)

- Dashboard

  ![dashboard](https://github.com/user-attachments/assets/3887ef3e-79ee-447f-8194-8775992fd0b3)

- Rent Payment

  ![rent](https://github.com/user-attachments/assets/af6b9d7c-8e1d-4a76-846c-f64de9f297cf)

- Receipt/Invoice

  ![receipt](https://github.com/user-attachments/assets/47d84480-54d3-4e0b-bc63-70038f8cf61a)

- Financial Report

  ![financial](https://github.com/user-attachments/assets/bf0081dc-0b51-42de-bc3a-e93bff8ca1b7)

- Boarders Profile

  ![boarders](https://github.com/user-attachments/assets/06a7353b-0d78-4c74-a729-a1bf2af108f5)

- Communication

  ![communication](https://github.com/user-attachments/assets/fd60a7f9-9b13-4b8d-9c01-613a636bf3b1)

## Developers

Project_PRS 

Prixane Jade Gales https://github.com/Pjadeyy

Stephanie Jane Eleccion https://github.com/stephjx

Rechael Antonette T. Salise https://github.com/rechaelantonettesalise

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contact

For any inquiries or issues, please contact us at [support@Bhousebillinghub.com](mailto:support@Bhousebillinghub.com).


