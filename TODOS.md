# Bloxtor To-Dos (Tasks)

- Prepare the Bloxtor code to work with php 8 (so far the stable version of php is 7.2) - working progress
- Integrate Bloxtor with Laravel e Symphony, so the developers can use this thrid-party frameworks.
- Integrate Bloxtor with Github so the developer can have code versioning. 
- Integrate AI in the Page, Template and View editors to create beautiful HTML automatically and add that html to the correspondent region.
- Integrate AI in the Logic editor to create php code automatically, according with a developer description.
- Integrate AI in the SQL editor to create statements automatically, according with a developer description.
- Integrate Bloxtor with Zapier, where the user saves its credentials in Bloxtor and logs in to Zappier directly from Bloxtor (through an iframe).
- Integrate Bloxtor with Lucidchart, where the user saves its credentials in Bloxtor and logs in to Lucidchart directly from Bloxtor (through an iframe), to create diagrams.
- Integrate the Github account to be set automatically with the following apps:
	+ Agile apps to help the developers to manage their projects: Zube, ZenHub, Codetree, "Azure Boards" and "GitKraken Boards"; 
	+ Apps to analyse the performance of the developers: DeepAffects, "Ranked development", WakaTime, GitView and Teamlytics; 
	+ Apps to check the code quality: Codacy, DeepSource, "Code Inspector", CodeFactor, "CodeImprover Duplication" and "Code Climate"; 
	+ Apps for easy deployment: Buddy, CircleCI and "CloudBees CodeShip"; 
	+ Apps for monitoring: Rollbar, ZenCrepes and "Meercode | CI Monitoring"; 
	+ Apps to see diagrams on Github: "Lucidchart Connector".
- Redesign the "Citizen Development" workspace and make it available by default.
- Create a new workspace called "Ninja", which is basically a rudimentary file manager to manage all files including .htaccess and other reserved files. The idea is to give the developer permission to edit all files, as if he were connected via ssh.
- Add Foreign Keys syncronization from the DB diagram to DB Server.
- Add a new task in the logic editor to perform a calculation on an attribute in a list. eg: if I want to calculate the sum of an attribute from a list of records, instead of creating a diagram with the loop task, I call this new task that calculates the sum of an attribute from that list of records. Something like in Excel.
- In the SLA/Resources section (of the Page editor), Add a new action to perform a calculation on an attribute in a list. eg: if I want to calculate the sum of an attribute from a list of records, I call this new action that calculates the sum of an attribute from that list of records.
- Add the option in the SQL editor to be able to add functions such as sum, count, etc... but in a visual way without the user needing to know these keywords.
- In the Page Editor, find a way to execute functions directly in the html for certain dynamic data. For example: I may want to show the correspondent month, in full text, of a timestamp attribute from a records list. Find a away to do this in a user-friendly and visual way.
- Add FTPManager that allows deployment via FTP, for servers that do not allow SSH. Also prepare deployment code with FTP functionality. When deploying, give the user option to use SSH or FTP.
- Change the database diagram to allow table alias, then update the entire system to automatically load the table alias instead of asking the user because it's annoying.
- Add websockets to MyJSResourceLib.js where dynamic data from database are automatically updated through websockets to be faster. Basically for each link, create an attribute called “prefetch” which can be:
	+ "intent": communicates with the server and fetches the new page but does not render it. (rendering means creating the final html to be displayed.)
	+ "render": communicates with the server, fetches the new page and renders it.
	+ "none": does not do anything. It is the same than not having this attribute.
	Create this new feature based on certain template regions, where the client only requests the html for certain regions.
- Create search box in the Logic Editor and Page Editor so the developer can find available tasks/widgets easly.
- Create redirect task in the Logic Editor. Basically calls the "header: location" php function.
- Create json_encode and json_decode tasks in the Logic Editor.
- In the SLA/Resources section of the page editor, change the "conditions" input field to be more user-friendly, when this field contains php code. The idea is to convert this user input to no-code, where the developer don't need to write PHP code. Basically, create a code reader and converter for this input field that displays the correspondent code in a friendly way.
- Improve the framework's dark theme.
- Finish the cache settings in the "Advanced Settings Tab" of the Page Editor.
- Change the word "Entity" to "Page" in the phpframework code. Currently an entity is a Page in Bloxtor. However, the people relate "entity" with a database model, which gets confused. So we need to change all files and replace "entity" with "page" word. Also change EVC (Entity-View-Controller) to PVC (Page-View-Controller).

Other tasks will be added as these tasks are completed.

