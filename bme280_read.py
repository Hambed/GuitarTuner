import time
import board
import sys
import json
from adafruit_bme280 import basic as adafruit_bme280

i2c = board.I2C()
bme280 = adafruit_bme280.Adafruit_BME280_I2C(i2c)

data = {
    "temperature":round(bme280.temperature, 2),
    "humidity":round(bme280.humidity, 2)
}

print(json.dumps(data))
