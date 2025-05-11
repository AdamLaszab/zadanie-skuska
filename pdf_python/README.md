ubuntu ma python default nainstalovany
python verzia by mala byt 3.12.x

sudo apt install python3-pip python3-venv

pull z githubu
v repozitary aktivovat venv
```
python3 -m venv venv
```

zapnutie venv -> source venv/bin/activate

vypnutie-> deactivate

nainstalovanie dependencies ked zapnuty venv
```
pip install -r requirements.txt
```

export dependencies ked zapnuty venv
```
pip freeze > requirements.txt
```