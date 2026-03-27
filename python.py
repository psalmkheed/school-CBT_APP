greetings = "Hello Everyone, "

first_name = "Babatunde"

last_name = "Oluwasegun"

other_name = "Samuel"

skills = "I am an accountant, that use python for my accounting calculation"

print(greetings, "My name is", first_name, last_name, other_name, skills)

opening_stock = 50000
purchases = 30000
carriage_inward = 5000
return_outward = 2500
closing_stock = 60000

print("Opening Stock: ", opening_stock)
print("Purchases: ", purchases)
print("Carriage Inward: ", carriage_inward)
print("Return Outward: ", return_outward)
print("Closing Stock: ", closing_stock)

print("Cost of Goods Available for Sale: ", opening_stock + purchases + carriage_inward - return_outward)
print("Cost of Goods Sold: ", opening_stock + purchases + carriage_inward - return_outward - closing_stock)