import { Bucket } from '../types/bucket';

export const initialBuckets: Bucket[] = [
{ id: 'b1', name: 'Mortgage', priority: 1, current: 2000, target: 2000 },
{ id: 'b2', name: 'Groceries', priority: 2, current: 450, target: 800 },
{
  id: 'b3',
  name: 'Emergency Fund',
  priority: 3,
  current: 10000,
  target: 25000
},
{ id: 'b4', name: 'Water Bill', priority: 4, current: 0, target: 60 },
{ id: 'b5', name: 'Electric Bill', priority: 5, current: 85, target: 120 },
{ id: 'b6', name: 'Internet', priority: 6, current: 65, target: 65 },
{ id: 'b7', name: 'Car Payment', priority: 7, current: 350, target: 450 },
{ id: 'b8', name: 'Gas & Fuel', priority: 8, current: 0, target: 200 },
{ id: 'b9', name: 'Phone Bill', priority: 9, current: 45, target: 45 },
{
  id: 'b10',
  name: 'Streaming Services',
  priority: 10,
  current: 30,
  target: 45
},
{ id: 'b11', name: 'Gym Membership', priority: 11, current: 0, target: 50 },
{ id: 'b12', name: 'Dining Out', priority: 12, current: 0, target: 150 },
{ id: 'b13', name: 'Clothing', priority: 13, current: 0, target: 100 },
{ id: 'b14', name: 'Pet Expenses', priority: 14, current: 0, target: 75 },
{
  id: 'b15',
  name: 'Vacation Fund',
  priority: 15,
  current: 2500,
  target: 5000
}];