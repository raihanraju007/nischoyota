# Bangladesh locations

`divisions.json`, `districts.json`, `upazilas.json` come from
[nuhil/bangladesh-geocode](https://github.com/nuhil/bangladesh-geocode) (MIT License, © Nuhil Mehdy),
reduced to `id`, parent id, `name`, `name_bn`. English names updated to the official spellings
Chattogram, Barishal, Cumilla and Cox's Bazar.

Dhaka Metropolitan thanas are not in that dataset; they are added by `LocationSeeder`.
Seeded by `Database\Seeders\LocationSeeder`.
