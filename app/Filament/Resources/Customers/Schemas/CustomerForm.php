<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->placeholder('Enter customer name / clinic / hospital name')
                    ->helperText('e.g., Al-Amal Hospital, Dr. Khaled Clinic'),

                TextInput::make('code')
                    ->default(fn () => 'CUST-' . strtoupper(Str::random(6)))
                    ->readOnly()
                    ->required()
                    ->unique(table: 'customers', ignoreRecord: true)
                    ->helperText('Automatically generated customer ID (Read Only)'),

                TextInput::make('contact_name')
                    ->placeholder('Primary contact person name'),

                TextInput::make('contact_email')
                    ->email()
                    ->placeholder('contact@email.com'),

                Grid::make(3)
                    ->schema([
                        Select::make('phone_country_code')
                            ->options(self::getCountryCodes())
                            ->searchable()
                            ->required()
                            ->label('Country Code')
                            ->columnSpan(1)
                            ->afterStateHydrated(function ($state, $record, callable $set) {
                                if ($record && $record->contact_phone) {
                                    if (preg_match('/^(\+[0-9]+)\s*(.*)/', $record->contact_phone, $matches)) {
                                        $set('phone_country_code', $matches[1]);
                                    }
                                }
                            })
                            ->dehydrated(false),
                        
                        TextInput::make('phone_number')
                            ->tel()
                            ->required()
                            ->placeholder('e.g. 501234567')
                            ->label('Phone Number')
                            ->columnSpan(2)
                            ->afterStateHydrated(function ($state, $record, callable $set) {
                                if ($record && $record->contact_phone) {
                                    if (preg_match('/^(\+[0-9]+)\s*(.*)/', $record->contact_phone, $matches)) {
                                        $set('phone_number', $matches[2]);
                                    } else {
                                        $set('phone_number', $record->contact_phone);
                                    }
                                }
                            })
                            ->dehydrated(false)
                    ])
                    ->columnSpanFull(),

                Hidden::make('contact_phone')
                    ->dehydrated(true)
                    ->dehydrateStateUsing(fn ($get) => trim(($get('phone_country_code') ?? '') . ' ' . ($get('phone_number') ?? ''))),

                TextInput::make('hms_server_url')
                    ->url()
                    ->placeholder('e.g. https://hms.seeha.tech')
                    ->helperText('The main domain URL of the customer\'s clinical HMS portal on the cloud.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state && empty($get('hms_api_url'))) {
                            $baseUrl = rtrim($state, '/');
                            $set('hms_api_url', $baseUrl . '/api');
                        }
                    }),

                TextInput::make('hms_api_url')
                    ->url()
                    ->placeholder('e.g. https://hms.seeha.tech/api')
                    ->helperText('The API endpoint used by the desktop app. Automatically generated from the server URL.'),

                Select::make('max_devices')
                    ->options([
                        1 => '1 Device (Single Terminal)',
                        5 => '1 - 5 Devices',
                        10 => '5 - 10 Devices',
                        20 => '10 - 20 Devices',
                        50 => '20 - 50 Devices',
                        100 => '50 - 100 Devices',
                        999 => 'Unlimited Devices',
                    ])
                    ->required()
                    ->default(1)
                    ->helperText('Maximum number of active terminals allowed under this customer\'s account.'),

                Select::make('license_type')
                    ->options([
                        'single' => 'Single Terminal',
                        'multi' => 'Multi Terminal',
                        'clinic' => 'Clinic License',
                        'hospital' => 'Hospital License',
                        'unlimited' => 'Unlimited License',
                    ])
                    ->required()
                    ->default('single'),

                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'terminated' => 'Terminated',
                    ])
                    ->required()
                    ->default('active'),

                Textarea::make('notes')
                    ->columnSpanFull()
                    ->placeholder('Add internal notes here (e.g. clinic location, contract details, etc.)'),
            ]);
    }

    private static function getCountryCodes(): array
    {
        return [
            '+93' => 'Afghanistan (+93)',
            '+355' => 'Albania (+355)',
            '+213' => 'Algeria (+213)',
            '+376' => 'Andorra (+376)',
            '+244' => 'Angola (+244)',
            '+1264' => 'Anguilla (+1264)',
            '+1268' => 'Antigua & Barbuda (+1268)',
            '+54' => 'Argentina (+54)',
            '+374' => 'Armenia (+374)',
            '+297' => 'Aruba (+297)',
            '+61' => 'Australia (+61)',
            '+43' => 'Austria (+43)',
            '+994' => 'Azerbaijan (+994)',
            '+1242' => 'Bahamas (+1242)',
            '+973' => 'Bahrain (+973)',
            '+880' => 'Bangladesh (+880)',
            '+1246' => 'Barbados (+1246)',
            '+375' => 'Belarus (+375)',
            '+32' => 'Belgium (+32)',
            '+501' => 'Belize (+501)',
            '+229' => 'Benin (+229)',
            '+1441' => 'Bermuda (+1441)',
            '+975' => 'Bhutan (+975)',
            '+591' => 'Bolivia (+591)',
            '+387' => 'Bosnia & Herzegovina (+387)',
            '+267' => 'Botswana (+267)',
            '+55' => 'Brazil (+55)',
            '+1284' => 'British Virgin Islands (+1284)',
            '+673' => 'Brunei (+673)',
            '+359' => 'Bulgaria (+359)',
            '+226' => 'Burkina Faso (+226)',
            '+257' => 'Burundi (+257)',
            '+855' => 'Cambodia (+855)',
            '+237' => 'Cameroon (+237)',
            '+1' => 'Canada / USA (+1)',
            '+238' => 'Cape Verde (+238)',
            '+1345' => 'Cayman Islands (+1345)',
            '+236' => 'Central African Republic (+236)',
            '+235' => 'Chad (+235)',
            '+56' => 'Chile (+56)',
            '+86' => 'China (+86)',
            '+57' => 'Colombia (+57)',
            '+269' => 'Comoros (+269)',
            '+242' => 'Congo - Brazzaville (+242)',
            '+243' => 'Congo - Kinshasa (+243)',
            '+682' => 'Cook Islands (+682)',
            '+506' => 'Costa Rica (+506)',
            '+385' => 'Croatia (+385)',
            '+53' => 'Cuba (+53)',
            '+357' => 'Cyprus (+357)',
            '+420' => 'Czech Republic (+420)',
            '+45' => 'Denmark (+45)',
            '+253' => 'Djibouti (+253)',
            '+1767' => 'Dominica (+1767)',
            '+1809' => 'Dominican Republic (+1809)',
            '+1829' => 'Dominican Republic (+1829)',
            '+1849' => 'Dominican Republic (+1849)',
            '+593' => 'Ecuador (+593)',
            '+20' => 'Egypt (+20)',
            '+503' => 'El Salvador (+503)',
            '+240' => 'Equatorial Guinea (+240)',
            '+291' => 'Eritrea (+291)',
            '+372' => 'Estonia (+372)',
            '+251' => 'Ethiopia (+251)',
            '+500' => 'Falkland Islands (+500)',
            '+298' => 'Faroe Islands (+298)',
            '+679' => 'Fiji (+679)',
            '+358' => 'Finland (+358)',
            '+33' => 'France (+33)',
            '+594' => 'French Guiana (+594)',
            '+689' => 'French Polynesia (+689)',
            '+241' => 'Gabon (+241)',
            '+220' => 'Gambia (+220)',
            '+995' => 'Georgia (+995)',
            '+49' => 'Germany (+49)',
            '+233' => 'Ghana (+233)',
            '+350' => 'Gibraltar (+350)',
            '+30' => 'Greece (+30)',
            '+299' => 'Greenland (+299)',
            '+1473' => 'Grenada (+1473)',
            '+590' => 'Guadeloupe (+590)',
            '+1671' => 'Guam (+1671)',
            '+502' => 'Guatemala (+502)',
            '+224' => 'Guinea (+224)',
            '+245' => 'Guinea-Bissau (+245)',
            '+592' => 'Guyana (+592)',
            '+509' => 'Haiti (+509)',
            '+504' => 'Honduras (+504)',
            '+852' => 'Hong Kong (+852)',
            '+36' => 'Hungary (+36)',
            '+354' => 'Iceland (+354)',
            '+91' => 'India (+91)',
            '+62' => 'Indonesia (+62)',
            '+98' => 'Iran (+98)',
            '+964' => 'Iraq (+964)',
            '+353' => 'Ireland (+353)',
            '+972' => 'Israel (+972)',
            '+39' => 'Italy (+39)',
            '+1876' => 'Jamaica (+1876)',
            '+81' => 'Japan (+81)',
            '+962' => 'Jordan (+962)',
            '+7' => 'Kazakhstan / Russia (+7)',
            '+254' => 'Kenya (+254)',
            '+686' => 'Kiribati (+686)',
            '+850' => 'North Korea (+850)',
            '+82' => 'South Korea (+82)',
            '+965' => 'Kuwait (+965)',
            '+996' => 'Kyrgyzstan (+996)',
            '+856' => 'Laos (+856)',
            '+371' => 'Latvia (+371)',
            '+961' => 'Lebanon (+961)',
            '+266' => 'Lesotho (+266)',
            '+231' => 'Liberia (+231)',
            '+218' => 'Libya (+218)',
            '+423' => 'Liechtenstein (+423)',
            '+370' => 'Lithuania (+370)',
            '+352' => 'Luxembourg (+352)',
            '+853' => 'Macau (+853)',
            '+389' => 'Macedonia (+389)',
            '+261' => 'Madagascar (+261)',
            '+265' => 'Malawi (+265)',
            '+60' => 'Malaysia (+60)',
            '+960' => 'Maldives (+960)',
            '+223' => 'Mali (+223)',
            '+356' => 'Malta (+356)',
            '+692' => 'Marshall Islands (+692)',
            '+596' => 'Martinique (+596)',
            '+222' => 'Mauritania (+222)',
            '+230' => 'Mauritius (+230)',
            '+262' => 'Mayotte (+262)',
            '+52' => 'Mexico (+52)',
            '+691' => 'Micronesia (+691)',
            '+373' => 'Moldova (+373)',
            '+377' => 'Monaco (+377)',
            '+976' => 'Mongolia (+976)',
            '+1664' => 'Montserrat (+1664)',
            '+212' => 'Morocco (+212)',
            '+258' => 'Mozambique (+258)',
            '+95' => 'Myanmar (+95)',
            '+264' => 'Namibia (+264)',
            '+674' => 'Nauru (+674)',
            '+977' => 'Nepal (+977)',
            '+31' => 'Netherlands (+31)',
            '+687' => 'New Caledonia (+687)',
            '+64' => 'New Zealand (+64)',
            '+505' => 'Nicaragua (+505)',
            '+227' => 'Niger (+227)',
            '+234' => 'Nigeria (+234)',
            '+683' => 'Niue (+683)',
            '+672' => 'Norfolk Island (+672)',
            '+1670' => 'Northern Mariana Islands (+1670)',
            '+47' => 'Norway (+47)',
            '+968' => 'Oman (+968)',
            '+92' => 'Pakistan (+92)',
            '+680' => 'Palau (+680)',
            '+970' => 'Palestine (+970)',
            '+507' => 'Panama (+507)',
            '+675' => 'Papua New Guinea (+675)',
            '+595' => 'Paraguay (+595)',
            '+51' => 'Peru (+51)',
            '+63' => 'Philippines (+63)',
            '+48' => 'Poland (+48)',
            '+351' => 'Portugal (+351)',
            '+1787' => 'Puerto Rico (+1787)',
            '+1939' => 'Puerto Rico (+1939)',
            '+974' => 'Qatar (+974)',
            '+262' => 'Reunion (+262)',
            '+40' => 'Romania (+40)',
            '+250' => 'Rwanda (+250)',
            '+685' => 'Samoa (+685)',
            '+378' => 'San Marino (+378)',
            '+239' => 'Sao Tome & Principe (+239)',
            '+966' => 'Saudi Arabia (+966)',
            '+221' => 'Senegal (+221)',
            '+381' => 'Serbia (+381)',
            '+248' => 'Seychelles (+248)',
            '+232' => 'Sierra Leone (+232)',
            '+65' => 'Singapore (+65)',
            '+421' => 'Slovakia (+421)',
            '+386' => 'Slovenia (+386)',
            '+677' => 'Solomon Islands (+677)',
            '+252' => 'Somalia (+252)',
            '+27' => 'South Africa (+27)',
            '+34' => 'Spain (+34)',
            '+94' => 'Sri Lanka (+94)',
            '+290' => 'St. Helena (+290)',
            '+1869' => 'St. Kitts & Nevis (+1869)',
            '+1758' => 'St. Lucia (+1758)',
            '+508' => 'St. Pierre & Miquelon (+508)',
            '+1784' => 'St. Vincent & Grenadines (+1784)',
            '+249' => 'Sudan (+249)',
            '+597' => 'Suriname (+597)',
            '+268' => 'Swaziland (+268)',
            '+46' => 'Sweden (+46)',
            '+41' => 'Switzerland (+41)',
            '+963' => 'Syria (+963)',
            '+886' => 'Taiwan (+886)',
            '+992' => 'Tajikistan (+992)',
            '+255' => 'Tanzania (+255)',
            '+66' => 'Thailand (+66)',
            '+228' => 'Togo (+228)',
            '+690' => 'Tokelau (+690)',
            '+676' => 'Tonga (+676)',
            '+1868' => 'Trinidad & Tobago (+1868)',
            '+216' => 'Tunisia (+216)',
            '+90' => 'Turkey (+90)',
            '+993' => 'Turkmenistan (+993)',
            '+1649' => 'Turks & Caicos Islands (+1649)',
            '+688' => 'Tuvalu (+688)',
            '+256' => 'Uganda (+256)',
            '+380' => 'Ukraine (+380)',
            '+971' => 'United Arab Emirates (+971)',
            '+44' => 'United Kingdom (+44)',
            '+598' => 'Uruguay (+598)',
            '+1340' => 'US Virgin Islands (+1340)',
            '+998' => 'Uzbekistan (+998)',
            '+678' => 'Vanuatu (+678)',
            '+58' => 'Venezuela (+58)',
            '+84' => 'Vietnam (+84)',
            '+681' => 'Wallis & Futuna (+681)',
            '+967' => 'Yemen (+967)',
            '+260' => 'Zambia (+260)',
            '+263' => 'Zimbabwe (+263)'
        ];
    }
}
