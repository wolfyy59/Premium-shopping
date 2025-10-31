create account in https://jsonbin.io/
then 
create a bin and put following code 

[
  {
    "id": 1,
    "timestamp": "2024-04-10T10:00:00.000Z",
    "customer": {
      "firstName": "Sample",
      "lastName": "Order",
      "phone": "000-000-0000",
      "wilaya": "Alger",
      "delivery": "domicile"
    },
    "products": [
      {
        "id": 1,
        "title": "Montre de Luxe Édition Or",
        "price": 299.99,
        "color": "Or",
        "quantity": 1
      }
    ],
    "total": 299.99,
    "totalItems": 1,
    "clientInfo": {
      "userAgent": "Sample",
      "language": "fr",
      "timezone": "UTC",
      "ip": "127.0.0.1"
    }
  }
]

next

inside index.html replace ur bin id api key 
example of config 

// Configuration for JSONBin.io - COMPLETE CONFIGURATION!
        const JSONBIN_CONFIG = {
            BIN_ID: '69047bc6d0ea881f40c91181', // Your Bin ID
            API_KEY: '$2a$10$HeuRw0voEvqzaHcdJgiiEOmLyEvvU6hVu3mc.3sQFF6g7D4B9kacm', // Your Master Key
            BASE_URL: 'https://api.jsonbin.io/v3/b'
        };



for order 1 use 1.jpg in root directory etc ..