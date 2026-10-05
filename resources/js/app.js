import 'leaflet/dist/leaflet.css';

import L from 'leaflet';

window.L = L;

import './echo';

import { subscribeToVehicleLocation } from './vehicle-location';
window.subscribeToVehicleLocation = subscribeToVehicleLocation;

import { subscribeToNotifications } from './notifications';
window.subscribeToNotifications = subscribeToNotifications;