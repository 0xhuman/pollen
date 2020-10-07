![](ignore/pollenlogoL.png)
# USSD Savings Circles 🦄 🌍 🚀 

## *(TL;DR) 🔑*
*[Pollen](https://www.fourthlinelimited.com/finance) is a platform for African farmers to access finanical 💸, crop 🌱, and weather services 🌦. Specifically, Pollen allows users to participate in digital savings circles (aka village banking), check the weekly forecast, and find local buyers for their crops. Pollen uses Unstructured Supplementary Service Data [(USSD)](https://en.wikipedia.org/wiki/Unstructured_Supplementary_Service_Data) to reach users without a smartphone or internet access and maximize prosperity. This is done with a PHP server and MySQL database. To manage user funds in a secure, transparent, and trustless manor, we use the [Celo Blockchain](https://www.celo.org). Mobile-money (i.e. [M-Pesa](https://en.wikipedia.org/wiki/M-Pesa)) serves as the on- and off-ramps to cUSD, a stablecoin on Celo that is pegged to the US Dollar. This architecture can be expanded beyond savings circles to include interest-barring savings, lotteries, round-up savings, crypto investing, and much more 🚀* 

## Overview 📚
#### USSD Flow Process Diagram
![](ignore/ussdflowdiagram.PNG)

#### Tech Stack Diagram
![](ignore/techstackdiagram.jpg)

#### Celo
* High level overview and capability, value adds, competitive advantage

## APIs 🌐
#### Africa's Talking
#### OpenWeather
#### KotaniPay
#### Crop Prices

## Database Design 🛠
#### Users
* Username, Phone Number, Location, Secret Pin
#### Session Levels
* Session ID,  Date, Phone Number, Level, Action (Circle Select)
#### Circles
* Circle ID, Circle Name, Invite Code
#### Circle Invites
* Circle ID, Phone Number (Inviter), Phone Number (Invitee), Date
#### Circle Members
* Circle ID, Phone Number
#### Circle Proposals
* Circle ID, Proposal ID (unique)
* Quorem (e.g. 100%), Threshold (e.g. 51%)...
* Action (e.g. add members, request funds, set interest rates, etc)
* Value (e.g. phone #, amount of funds, rate, etc)
* Phone Number (Proposer)
#### Circle Votes
* Circle ID (delete?), Proposal ID, Phone Number (Voter), Vote (Yes or No)...do we need to count them?
#### Circle Transactions
* Phone Number (Sender or Circle), Phone Number (Receiver), Date, Amount
#### City Coordinates
* City Name, Lat, Lon

## Code Walk Through 🚶🏽‍♀️
#### User Registration
#### Tracking User Session with Levels
#### OpenWeather API

#### Requerying data
* storing user actions (circle select)
* checking user level
* pulling circle name
* Should this all be done at the very beginning? Query all data ahead of time, then just requery with Available[]?
#### Generalizability
* database design
* strtolower
* strtoupper

## Resources 😅
#### Africa's Talking Examples
#### PHP Help
#### MySQL Help
