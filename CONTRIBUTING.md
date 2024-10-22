# Bloxtor Contribution Rules

Before contributing, please read carefully our *Bloxtor Licence Agreement*.

When contributing to our projects, please first discuss the change you wish to make via issue or any other available method with the owners of the correspondent repository before making a change.

Please note you must accept and should follow the *Bloxtor Code of Conduct* in all your interactions with the project.

### Pull request process guide-lines:

1. Ensure any runnable temporary or dummy files are removed before doing a commit.
2. Update the "Version" section at README.md with details of changes to the project, this includes new environment variables, exposed ports, useful file locations and container parameters.
3. Increase the version numbers in any examples files and the README.md to the new version that this Pull Request would represent.
4. Squash all your commits before creating the Pull Request. Don't commit unfinished work. (One commit per tested feature or fixed issue)
5. You may merge the Pull Request in once you have our official authorization or the sign-off of other developer, or if you do not have permission to do that, you may request the second reviewer to merge it for you.
6. Do not change anything that you don't understand. Ask us first.

### To contribute please follow the steps below:
(For more details please go to this [tutorial](https://docs.github.com/en/get-started/exploring-projects-on-github/contributing-to-a-project))

1. Login to your [GitHub](https://github.com/login) account.
2. Open the Bloxtor [repo](https://github.com/a19836/bloxtor) in your browser.
3. Fork this repo, i.e. forking the Bloxtor repo, means that you will create a "cloned" repo in your account, but where this new repo is linked to our Bloxtor repo. By default, GitHub fills in the name and description of your new repo, if not, give it a name and type a description that you like. Then click **Create fork**.
4. Clone your repo on your local server, this is, copy the url for your new repo, go to your local terminal and type:
```
#Replace <url> by your repo url. Eg. git clone https://github.com/YOUR-USERNAME/bloxtor
git clone <url>
```
5. Create a branch to work on, by typing in your terminal:
```
#Create branch
git branch BRANCH-NAME

#Switch to branch
git checkout BRANCH-NAME
```
6. Go ahead and make a few changes to the project, using your favorite editor...
7. When you're ready to submit your changes, go back to your terminal and stage and commit your changes, by typing:
```
#"git add .": means that want to include all of your changes in the commit
git add .

#Or add specific files
git add FILE-PATH-1 FILE-PATH-2

#Commit your previously added files
#Note that to proceed with this step, you must already have your git variables user.email and user.name configured.
git commit -m "a short description of the change"

#If you don't have yet your variables configured please type:
git config user.email "you@example.com"
git config user.name "Your Name"
git commit -m "a short description of the change"
```
8. Right now, your changes only exist locally. When you're ready to push your changes up to GitHub, push your changes to the remote.
```
git push

#or on error, type:
git push --set-upstream origin BRANCH-NAME
```
9. At last, you're ready to propose changes into the main project! This is the final step in producing a fork of original Bloxtor repo, and the most important. To continue, head on over to the repository on GitHub where your project lives.
You'll see a banner indicating that your branch had recent pushes. Click **Compare & pull request**.

	GitHub will bring you to a page that shows the differences between your fork and the original repository. Enter a title and a description of your changes. It's important to provide as much useful information and a rationale for why you're making this pull request in the first place. We need to be able to determine whether your change is as useful to everyone as you think it is. Finally, click **Create pull request**.
10. Wait until you get our answer... Pull requests are an area for discussion. Don't be offended if we reject your pull request, or asks for more information on why it's been made.
11. After your request be accepted and closed, you can then delete your forked repo, i.e from your local server and from your GitHub account.

