<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BreedsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('breeds')->insert([

            // ============================
            //  GATUNEK: PIES (species_id = 1)
            // ============================

            ['species_id' => 1, 'breed_PL' => 'Nie znam', 'breed_EN' => 'Unknown'],

            // --- Grupa 1: Owczarki i psy pasterskie ---
            ['species_id' => 1, 'breed_PL' => 'Owczarek niemiecki', 'breed_EN' => 'German Shepherd'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek podhalański', 'breed_EN' => 'Polish Tatra Sheepdog'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek belgijski', 'breed_EN' => 'Belgian Shepherd'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek szkocki collie', 'breed_EN' => 'Rough Collie'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek szkocki krótkowłosy', 'breed_EN' => 'Smooth Collie'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek australijski', 'breed_EN' => 'Australian Shepherd'],
            ['species_id' => 1, 'breed_PL' => 'Border collie', 'breed_EN' => 'Border Collie'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek staroangielski bobtail', 'breed_EN' => 'Old English Sheepdog'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek holenderski', 'breed_EN' => 'Dutch Shepherd'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek chorwacki', 'breed_EN' => 'Croatian Sheepdog'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek pirenejski', 'breed_EN' => 'Pyrenean Shepherd'],
            ['species_id' => 1, 'breed_PL' => 'Owczarek kataloński', 'breed_EN' => 'Catalan Sheepdog'],

            // --- Grupa 2: Pinczery, molosy, sznaucery ---
            ['species_id' => 1, 'breed_PL' => 'Doberman', 'breed_EN' => 'Dobermann'],
            ['species_id' => 1, 'breed_PL' => 'Rottweiler', 'breed_EN' => 'Rottweiler'],
            ['species_id' => 1, 'breed_PL' => 'Sznaucer miniaturowy', 'breed_EN' => 'Miniature Schnauzer'],
            ['species_id' => 1, 'breed_PL' => 'Sznaucer średni', 'breed_EN' => 'Standard Schnauzer'],
            ['species_id' => 1, 'breed_PL' => 'Sznaucer olbrzym', 'breed_EN' => 'Giant Schnauzer'],
            ['species_id' => 1, 'breed_PL' => 'Bokser', 'breed_EN' => 'Boxer'],
            ['species_id' => 1, 'breed_PL' => 'Dog niemiecki', 'breed_EN' => 'Great Dane'],
            ['species_id' => 1, 'breed_PL' => 'Bernardyn', 'breed_EN' => 'Saint Bernard'],
            ['species_id' => 1, 'breed_PL' => 'Mastif angielski', 'breed_EN' => 'English Mastiff'],
            ['species_id' => 1, 'breed_PL' => 'Mastif neapolitański', 'breed_EN' => 'Neapolitan Mastiff'],
            ['species_id' => 1, 'breed_PL' => 'Buldog angielski', 'breed_EN' => 'English Bulldog'],
            ['species_id' => 1, 'breed_PL' => 'Buldog francuski', 'breed_EN' => 'French Bulldog'],

            // --- Grupa 3: Teriery ---
            ['species_id' => 1, 'breed_PL' => 'Yorkshire terrier', 'breed_EN' => 'Yorkshire Terrier'],
            ['species_id' => 1, 'breed_PL' => 'Jack Russell terrier', 'breed_EN' => 'Jack Russell Terrier'],
            ['species_id' => 1, 'breed_PL' => 'Bull terrier', 'breed_EN' => 'Bull Terrier'],
            ['species_id' => 1, 'breed_PL' => 'American Staffordshire terrier', 'breed_EN' => 'American Staffordshire Terrier'],
            ['species_id' => 1, 'breed_PL' => 'West Highland White Terrier', 'breed_EN' => 'West Highland White Terrier'],
            ['species_id' => 1, 'breed_PL' => 'Skye terrier', 'breed_EN' => 'Skye Terrier'],
            ['species_id' => 1, 'breed_PL' => 'Fox terrier', 'breed_EN' => 'Fox Terrier'],

            // --- Grupa 4: Jamniki ---
            ['species_id' => 1, 'breed_PL' => 'Jamnik krótkowłosy', 'breed_EN' => 'Dachshund Smooth'],
            ['species_id' => 1, 'breed_PL' => 'Jamnik długowłosy', 'breed_EN' => 'Dachshund Longhaired'],
            ['species_id' => 1, 'breed_PL' => 'Jamnik szorstkowłosy', 'breed_EN' => 'Dachshund Wirehaired'],

            // --- Grupa 5: Szpice i psy pierwotne ---
            ['species_id' => 1, 'breed_PL' => 'Husky syberyjski', 'breed_EN' => 'Siberian Husky'],
            ['species_id' => 1, 'breed_PL' => 'Samoyed', 'breed_EN' => 'Samoyed'],
            ['species_id' => 1, 'breed_PL' => 'Akita inu', 'breed_EN' => 'Akita Inu'],
            ['species_id' => 1, 'breed_PL' => 'Shiba inu', 'breed_EN' => 'Shiba Inu'],
            ['species_id' => 1, 'breed_PL' => 'Alaskan malamute', 'breed_EN' => 'Alaskan Malamute'],
            ['species_id' => 1, 'breed_PL' => 'Chow chow', 'breed_EN' => 'Chow Chow'],
            ['species_id' => 1, 'breed_PL' => 'Szpic niemiecki', 'breed_EN' => 'German Spitz'],
            ['species_id' => 1, 'breed_PL' => 'Pomeranian', 'breed_EN' => 'Pomeranian'],
            ['species_id' => 1, 'breed_PL' => 'Beagle', 'breed_EN' => 'Beagle'],
            ['species_id' => 1, 'breed_PL' => 'Harrier', 'breed_EN' => 'Harrier'],
            ['species_id' => 1, 'breed_PL' => 'Gończy polski', 'breed_EN' => 'Polish Hunting Dog'],
            ['species_id' => 1, 'breed_PL' => 'Ogar polski', 'breed_EN' => 'Polish Hound'],
            ['species_id' => 1, 'breed_PL' => 'Basset hound', 'breed_EN' => 'Basset Hound'],
            ['species_id' => 1, 'breed_PL' => 'Bloodhound', 'breed_EN' => 'Bloodhound'],
            ['species_id' => 1, 'breed_PL' => 'Dalmatyńczyk', 'breed_EN' => 'Dalmatian'],
            ['species_id' => 1, 'breed_PL' => 'Rhodesian ridgeback', 'breed_EN' => 'Rhodesian Ridgeback'],
            ['species_id' => 1, 'breed_PL' => 'Wyżeł niemiecki krótkowłosy', 'breed_EN' => 'German Shorthaired Pointer'],
            ['species_id' => 1, 'breed_PL' => 'Wyżeł niemiecki szorstkowłosy', 'breed_EN' => 'German Wirehaired Pointer'],
            ['species_id' => 1, 'breed_PL' => 'Wyżeł weimarski', 'breed_EN' => 'Weimaraner'],
            ['species_id' => 1, 'breed_PL' => 'Wyżeł węgierski krótkowłosy', 'breed_EN' => 'Hungarian Vizsla'],
            ['species_id' => 1, 'breed_PL' => 'Wyżeł węgierski szorstkowłosy', 'breed_EN' => 'Hungarian Wirehaired Vizsla'],
            ['species_id' => 1, 'breed_PL' => 'Seter irlandzki', 'breed_EN' => 'Irish Setter'],
            ['species_id' => 1, 'breed_PL' => 'Seter angielski', 'breed_EN' => 'English Setter'],
            ['species_id' => 1, 'breed_PL' => 'Seter szkocki Gordon', 'breed_EN' => 'Gordon Setter'],
            ['species_id' => 1, 'breed_PL' => 'Labrador retriever', 'breed_EN' => 'Labrador Retriever'],
            ['species_id' => 1, 'breed_PL' => 'Golden retriever', 'breed_EN' => 'Golden Retriever'],
            ['species_id' => 1, 'breed_PL' => 'Flat coated retriever', 'breed_EN' => 'Flat-Coated Retriever'],
            ['species_id' => 1, 'breed_PL' => 'Cocker spaniel angielski', 'breed_EN' => 'English Cocker Spaniel'],
            ['species_id' => 1, 'breed_PL' => 'Springer spaniel angielski', 'breed_EN' => 'English Springer Spaniel'],
            ['species_id' => 1, 'breed_PL' => 'Springer spaniel walijski', 'breed_EN' => 'Welsh Springer Spaniel'],
            ['species_id' => 1, 'breed_PL' => 'Nowofundland', 'breed_EN' => 'Newfoundland'],
            ['species_id' => 1, 'breed_PL' => 'Portugalski pies wodny', 'breed_EN' => 'Portuguese Water Dog'],
            ['species_id' => 1, 'breed_PL' => 'Mops', 'breed_EN' => 'Pug'],
            ['species_id' => 1, 'breed_PL' => 'Shih tzu', 'breed_EN' => 'Shih Tzu'],
            ['species_id' => 1, 'breed_PL' => 'Pekińczyk', 'breed_EN' => 'Pekingese'],
            ['species_id' => 1, 'breed_PL' => 'Chihuahua', 'breed_EN' => 'Chihuahua'],
            ['species_id' => 1, 'breed_PL' => 'Bichon frise', 'breed_EN' => 'Bichon Frise'],
            ['species_id' => 1, 'breed_PL' => 'Maltańczyk', 'breed_EN' => 'Maltese'],
            ['species_id' => 1, 'breed_PL' => 'Coton de Tulear', 'breed_EN' => 'Coton de Tulear'],
            ['species_id' => 1, 'breed_PL' => 'Papillon', 'breed_EN' => 'Papillon'],
            ['species_id' => 1, 'breed_PL' => 'Pomeranian', 'breed_EN' => 'Pomeranian'],
            ['species_id' => 1, 'breed_PL' => 'Lhasa apso', 'breed_EN' => 'Lhasa Apso'],
            ['species_id' => 1, 'breed_PL' => 'Cavalier King Charles spaniel', 'breed_EN' => 'Cavalier King Charles Spaniel'],
            ['species_id' => 1, 'breed_PL' => 'King Charles spaniel', 'breed_EN' => 'King Charles Spaniel'],
            ['species_id' => 1, 'breed_PL' => 'Chart afgański', 'breed_EN' => 'Afghan Hound'],
            ['species_id' => 1, 'breed_PL' => 'Chart angielski greyhound', 'breed_EN' => 'Greyhound'],
            ['species_id' => 1, 'breed_PL' => 'Whippet', 'breed_EN' => 'Whippet'],
            ['species_id' => 1, 'breed_PL' => 'Chart rosyjski borzoj', 'breed_EN' => 'Borzoi'],
            ['species_id' => 1, 'breed_PL' => 'Chart szkocki deerhound', 'breed_EN' => 'Scottish Deerhound'],
            ['species_id' => 1, 'breed_PL' => 'Chart irlandzki wilczarz', 'breed_EN' => 'Irish Wolfhound'],
            ['species_id' => 1, 'breed_PL' => 'Chart arabski saluki', 'breed_EN' => 'Saluki'],
            ['species_id' => 1, 'breed_PL' => 'Chart perski', 'breed_EN' => 'Sloughi'],

            // ============================
            // GATUNEK: KOT (species_id = 2)
            // ============================

            ['species_id' => 2, 'breed_PL' => 'Nie znam', 'breed_EN' => 'Unknown'],
            ['species_id' => 2, 'breed_PL' => 'Dachowiec', 'breed_EN' => 'Domestic Shorthair'],
            ['species_id' => 2, 'breed_PL' => 'Maine Coon', 'breed_EN' => 'Maine Coon'],
            ['species_id' => 2, 'breed_PL' => 'Ragdoll', 'breed_EN' => 'Ragdoll'],
            ['species_id' => 2, 'breed_PL' => 'Syjamski', 'breed_EN' => 'Siamese'],
            ['species_id' => 2, 'breed_PL' => 'Perski', 'breed_EN' => 'Persian'],
            ['species_id' => 2, 'breed_PL' => 'Brytyjski krótkowłosy', 'breed_EN' => 'British Shorthair'],
            ['species_id' => 2, 'breed_PL' => 'Brytyjski długowłosy', 'breed_EN' => 'British Longhair'],
            ['species_id' => 2, 'breed_PL' => 'Bengalski', 'breed_EN' => 'Bengal'],
            ['species_id' => 2, 'breed_PL' => 'Rosyjski niebieski', 'breed_EN' => 'Russian Blue'],
            ['species_id' => 2, 'breed_PL' => 'Norweski leśny', 'breed_EN' => 'Norwegian Forest Cat'],
            ['species_id' => 2, 'breed_PL' => 'Syberyjski', 'breed_EN' => 'Siberian'],
            ['species_id' => 2, 'breed_PL' => 'Sfinks', 'breed_EN' => 'Sphynx'],
            ['species_id' => 2, 'breed_PL' => 'Devon Rex', 'breed_EN' => 'Devon Rex'],
            ['species_id' => 2, 'breed_PL' => 'Cornish Rex', 'breed_EN' => 'Cornish Rex'],
            ['species_id' => 2, 'breed_PL' => 'Egipski Mau', 'breed_EN' => 'Egyptian Mau'],
            ['species_id' => 2, 'breed_PL' => 'Orientalny krótkowłosy', 'breed_EN' => 'Oriental Shorthair'],
            ['species_id' => 2, 'breed_PL' => 'Orientalny długowłosy', 'breed_EN' => 'Oriental Longhair'],
            ['species_id' => 2, 'breed_PL' => 'Turecki van', 'breed_EN' => 'Turkish Van'],
            ['species_id' => 2, 'breed_PL' => 'Turecki angora', 'breed_EN' => 'Turkish Angora'],
            ['species_id' => 2, 'breed_PL' => 'Manx', 'breed_EN' => 'Manx'],
            ['species_id' => 2, 'breed_PL' => 'American Shorthair', 'breed_EN' => 'American Shorthair'],
            ['species_id' => 2, 'breed_PL' => 'American Curl', 'breed_EN' => 'American Curl'],
            ['species_id' => 2, 'breed_PL' => 'American Bobtail', 'breed_EN' => 'American Bobtail'],
            ['species_id' => 2, 'breed_PL' => 'Japanese Bobtail', 'breed_EN' => 'Japanese Bobtail'],
            ['species_id' => 2, 'breed_PL' => 'Scottish Fold', 'breed_EN' => 'Scottish Fold'],
            ['species_id' => 2, 'breed_PL' => 'Scottish Straight', 'breed_EN' => 'Scottish Straight'],
            ['species_id' => 2, 'breed_PL' => 'Highland Fold', 'breed_EN' => 'Highland Fold'],
            ['species_id' => 2, 'breed_PL' => 'Highland Straight', 'breed_EN' => 'Highland Straight'],
            ['species_id' => 2, 'breed_PL' => 'Bombajski', 'breed_EN' => 'Bombay'],
            ['species_id' => 2, 'breed_PL' => 'Himalajski', 'breed_EN' => 'Himalayan'],
            ['species_id' => 2, 'breed_PL' => 'Birman', 'breed_EN' => 'Birman'],
            ['species_id' => 2, 'breed_PL' => 'Chartreux', 'breed_EN' => 'Chartreux'],
            ['species_id' => 2, 'breed_PL' => 'Somalijski', 'breed_EN' => 'Somali'],
            ['species_id' => 2, 'breed_PL' => 'Abisyński', 'breed_EN' => 'Abyssinian'],
            ['species_id' => 2, 'breed_PL' => 'Ocicat', 'breed_EN' => 'Ocicat'],
            ['species_id' => 2, 'breed_PL' => 'Savannah', 'breed_EN' => 'Savannah'],
            ['species_id' => 2, 'breed_PL' => 'Serengeti', 'breed_EN' => 'Serengeti'],
            ['species_id' => 2, 'breed_PL' => 'LaPerm', 'breed_EN' => 'LaPerm'],
            ['species_id' => 2, 'breed_PL' => 'Munchkin', 'breed_EN' => 'Munchkin'],
            ['species_id' => 2, 'breed_PL' => 'Pixie-bob', 'breed_EN' => 'Pixie-bob'],
            ['species_id' => 2, 'breed_PL' => 'Selkirk Rex', 'breed_EN' => 'Selkirk Rex'],
            ['species_id' => 2, 'breed_PL' => 'Snowshoe', 'breed_EN' => 'Snowshoe'],
            ['species_id' => 2, 'breed_PL' => 'Tonkiński', 'breed_EN' => 'Tonkinese'],
            ['species_id' => 2, 'breed_PL' => 'Singapurski', 'breed_EN' => 'Singapura'],
            ['species_id' => 2, 'breed_PL' => 'Birmański', 'breed_EN' => 'Burmese'],
            ['species_id' => 2, 'breed_PL' => 'Bombay', 'breed_EN' => 'Bombay'],
            ['species_id' => 2, 'breed_PL' => 'Korat', 'breed_EN' => 'Korat'],
            ['species_id' => 2, 'breed_PL' => 'Ragamuffin', 'breed_EN' => 'Ragamuffin'],
            ['species_id' => 2, 'breed_PL' => 'Nebelung', 'breed_EN' => 'Nebelung'],
            ['species_id' => 2, 'breed_PL' => 'Peterbald', 'breed_EN' => 'Peterbald'],
            ['species_id' => 2, 'breed_PL' => 'Dwelf', 'breed_EN' => 'Dwelf'],
            ['species_id' => 2, 'breed_PL' => 'Ukrainian Levkoy', 'breed_EN' => 'Ukrainian Levkoy'],
            ['species_id' => 2, 'breed_PL' => 'Lykoi', 'breed_EN' => 'Lykoi'],
            ['species_id' => 2, 'breed_PL' => 'Toyger', 'breed_EN' => 'Toyger'],
            ['species_id' => 2, 'breed_PL' => 'Chausie', 'breed_EN' => 'Chausie'],
            ['species_id' => 2, 'breed_PL' => 'Khao Manee', 'breed_EN' => 'Khao Manee'],
            ['species_id' => 2, 'breed_PL' => 'Balinijski', 'breed_EN' => 'Balinese'],
            ['species_id' => 2, 'breed_PL' => 'Jawański', 'breed_EN' => 'Javanese'],
            ['species_id' => 2, 'breed_PL' => 'Cymric', 'breed_EN' => 'Cymric'],
            ['species_id' => 2, 'breed_PL' => 'German Rex', 'breed_EN' => 'German Rex'],
            ['species_id' => 2, 'breed_PL' => 'Burmilla', 'breed_EN' => 'Burmilla'],
            ['species_id' => 2, 'breed_PL' => 'Asian', 'breed_EN' => 'Asian'],
            ['species_id' => 2, 'breed_PL' => 'Tiffany', 'breed_EN' => 'Tiffanie'],
            ['species_id' => 2, 'breed_PL' => 'Sokoke', 'breed_EN' => 'Sokoke'],
            ['species_id' => 2, 'breed_PL' => 'Kurylski bobtail', 'breed_EN' => 'Kurilian Bobtail'],
            ['species_id' => 2, 'breed_PL' => 'Mekong bobtail', 'breed_EN' => 'Mekong Bobtail'],
            ['species_id' => 2, 'breed_PL' => 'Australian Mist', 'breed_EN' => 'Australian Mist'],
            ['species_id' => 2, 'breed_PL' => 'Bambino', 'breed_EN' => 'Bambino'],
            ['species_id' => 2, 'breed_PL' => 'Minuet', 'breed_EN' => 'Minuet'],
            ['species_id' => 2, 'breed_PL' => 'Foldex', 'breed_EN' => 'Foldex'],


        ]);
    }
}


