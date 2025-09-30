# woocommerce-mdl-currency-converter



=== WooCommerce MDL Currency Converter ===
Contributors: yourname
Tags: woocommerce, currency, converter, MDL, EUR, exchange rate
Requires at least: 5.8
Tested up to: 6.3
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Converteste automat preturile din EUR in MDL la checkout cu comision configurable.

== Description ==

Un plugin WooCommerce care converteste automat preturile din EUR in MDL la checkout, cu suport pentru:

* Curs de schimb automat de la Banca MAIB
* Comision configurable pentru conversie
* Cache pentru performanta
* Setari dedicate in admin WooCommerce
* Afisare detalii conversie la checkout
* Compatibilitate cu toate procesatorile de plata

== Installation ==

1. Incarca folderul 'woocommerce-mdl-currency-converter' in directorul '/wp-content/plugins/'
2. Activeaza plugin-ul din panoul de administrare WordPress
3. Mergi la WooCommerce > Setari > Converter MDL
4. Configureaza setarile dorite
5. Salveaza modificari

== Frequently Asked Questions ==

= De unde se preia cursul valutar? =

Plugin-ul preia cursul EUR/MDL de la Banca MAIB prin API-ul lor public.

= Cum functioneaza comisionul? =

Comisionul se aplica la suma convertita. Exemplu: 100 EUR × 19.50 curs × 2% comision = 1989.00 MDL.

= Pot folosi un curs manual? =

Da, poti dezactiva API-ul MAIB si seta un curs manual, sau poti forța folosirea cursului manual.

== Changelog ==

= 1.0.0 =
* Lansare initiala
