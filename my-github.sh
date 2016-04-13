
echo "# openshift-test" >> README.md
git init

#git add README2.md
git add .
git add shopping.zip
git config --global user.name "soheilpaper"
#git config --global user.email soheil_paper@yahoo.com
 #git commit --amend --reset-author
git commit -a  -m "first commit"

#git remote add origin https://soheilpaper:ss123456@github.com/soheilpaper/openshift-test.git
#git remote add origin https://github.com/soheilpaper/openshift-test.git
git config remote.origin.url https://soheilpaper:ss123456@github.com/soheilpaper/vb-eleasa.ir
#git pull 
git push -u origin master

